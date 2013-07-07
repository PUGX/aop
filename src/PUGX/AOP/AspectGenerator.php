<?php

namespace PUGX\AOP;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Generator\PhpProperty;
use CG\Proxy\GeneratorInterface;
use Doctrine\Common\Annotations\Reader;
use PUGX\AOP\Aspect\BaseAnnotation;
use ReflectionClass;
use ReflectionMethod;

/**
 * Base class that provides a common behavior for all aspects that you want to
 * inject in your code.
 */
class AspectGenerator implements GeneratorInterface
{

    /**
     * @var Reader
     */
    protected $annotationsReader;
    protected $annotationClassName;
    protected $requiredAspects = array();

    /**
     * Constructor
     *
     * @param $annotationsReader
     * @param $annotationClassName
     * @internal param $proxyDir
     */
    public function __construct(Reader $annotationsReader, $annotationClassName)
    {
        $this->annotationClassName = $annotationClassName;
        $this->annotationsReader = $annotationsReader;
    }

    /**
     * Returns the class that is used to read annotations for the current
     * aspect.
     *
     * @return string
     */
    protected function getAnnotationsClass()
    {
        return $this->annotationClassName;
    }

    public function hasAnnotation(\ReflectionClass $originalClass)
    {
        $methods = $this->getMethods($originalClass);
        $count = 0;
        /** @var ReflectionMethod $method */
        foreach ($methods as $method) {
            $count = count($this->getFilteredAnnotations($method));
            if ($count > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generates the necessary changes in the class.
     *
     * @param \ReflectionClass $originalClass
     * @param PhpClass $generatedClass The generated class
     * @return void
     */
    function generate(\ReflectionClass $originalClass, PhpClass $generatedClass)
    {
        foreach ($this->getMethods($originalClass) as $method) {
            $generatedClass->setMethod($this->generateMethod($method));
        }

        $this->prepareAspectInjection($originalClass, $generatedClass);
        $this->exposeSubjectClass($originalClass, $generatedClass);
    }

    /**
     * @param \ReflectionClass $class
     * @param boolean $publicOnly
     * @return array
     */
    public function getMethods(\ReflectionClass $class, $publicOnly = false)
    {
        $filter = \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PRIVATE;

        if (!$publicOnly) {
            $filter |= \ReflectionMethod::IS_PROTECTED;
        }

        return array_filter(
                        $class->getMethods($filter),
                        function($method) {
                            return !$method->isFinal() && !$method->isStatic();
                        }
        );
    }

    /**
     * generate the method object with its body and parameters
     *
     * @param ReflectionMethod $method
     * @param array|null $aspects
     * @return PhpMethod
     */
    protected function generateMethod(ReflectionMethod $method, $aspects = array())
    {
        $generator = new AspectCodeGenerator($method->class, $method->name);
        $genMethod = PhpMethod::fromReflection($method)
                ->setBody($this->generateMethodCode($method, $generator))
                ->setDocblock($this->generateDocBlock($method, $generator))
        ;
        foreach ($aspects as $aspect) {
            $genMethod->addParameter($this->generateAspectParameter($aspect));
        }

        return $genMethod;
    }

    /**
     * Generate the code for a given method with the required aspects triggered before or after
     * the method execution
     *
     * @param ReflectionMethod $method
     * @param \PUGX\AOP\AspectCodeGenerator $generator
     * @return string
     */
    protected function generateMethodCode(ReflectionMethod $method, AspectCodeGenerator $generator)
    {
        $params = implode(', ', $this->getMethodParameters($method));
        $generator->setParams($params);
        $implementedAspects = $this->getImplementedAspects($method, $generator);

        $aspectedMethod = new AspectedMethod;
        $aspectedMethod->addCode($generator->generateReflectionDeclarationCode(), AspectedMethod::DECLARATION);
        $aspectedMethod->setCode($implementedAspects[AspectedMethod::BEFORE], AspectedMethod::BEFORE);
        $aspectedMethod->addCode($generator->generateReflectionInvocationCode(), AspectedMethod::EXECUTION);
        $aspectedMethod->setCode($implementedAspects[AspectedMethod::VALIDATION], AspectedMethod::VALIDATION);
        $aspectedMethod->setCode($implementedAspects[AspectedMethod::AFTER], AspectedMethod::AFTER);
        $aspectedMethod->addCode($generator->generateReturningCode(), AspectedMethod::RETURNING);

        if ($method->name === '__construct') {
            foreach ($this->getRequiredAspects() as $aspect) {
                $aspectedMethod->addCode($generator->getSetterCode($aspect), AspectedMethod::INJECTION);
            }
        }

        return $aspectedMethod->getMethodCode();
    }

    protected function getMethodParameters(ReflectionMethod $method)
    {
        $params = array();
        foreach ($method->getParameters() as $param) {
            $params[] = '$' . $param->name;
        }
        return $params;
    }

    /**
     * Add the required properties and setters for the aspects used by the generated class
     *
     * @param \ReflectionClass $originalClass
     * @param \CG\Generator\PhpClass $generatedClass
     * @return \CG\Generator\PhpClass
     */
    protected function prepareAspectInjection(\ReflectionClass $originalClass, PhpClass $generatedClass)
    {
        $aspects = $this->getRequiredAspects();
        $generatedClass->setMethod($this->generateMethod($originalClass->getConstructor(), $aspects));
        $generator = new AspectCodeGenerator($originalClass->name);
        foreach ($aspects as $aspect) {
            $generatedClass->setProperty($this->generateAspectProperty($aspect, $generator));
            $generatedClass->setMethod($this->generateAspectSetter($aspect, $generator));
        }
        return $generatedClass;
    }

    /**
     * Adds a propery and a getter for the class name of the enhaced class
     *
     * @param \ReflectionClass $originalClass
     * @param \CG\Generator\PhpClass $generatedClass
     */
    protected function exposeSubjectClass(\ReflectionClass $originalClass, PhpClass $generatedClass)
    {
        $generatedClass->setProperty($this->generateProperty('_subjectClass', $originalClass->getName()));
        $generatedClass->setMethod($this->generateSubjectClassGetter());
    }

    /**
     * Create a property for the received aspect name
     *
     * @param string $aspect
     * @param \PUGX\AOP\AspectCodeGenerator $generator
     * @return \CG\Generator\PhpProperty
     */
    protected function generateAspectProperty($aspect, AspectCodeGenerator $generator)
    {
        return $this->generateProperty($generator->getAspectPropertyName($aspect));
    }

    /**
     * Create a property
     *
     * @param string $propertyName
     * @param mixed|null $value
     * @return \CG\Generator\PhpProperty
     */
    protected function generateProperty($propertyName, $value = null)
    {
        $property = new PhpProperty();
        $property
                ->setName($propertyName)
                ->setVisibility(PhpProperty::VISIBILITY_PRIVATE)
                ->setDefaultValue($value)
        ;
        return $property;
    }

    /**
     * Create a setter for the aspect property with the received aspect name
     *
     * @param string $aspect
     * @param \PUGX\AOP\AspectCodeGenerator $generator
     * @return \CG\Generator\PhpMethod
     */
    protected function generateAspectSetter($aspect, AspectCodeGenerator $generator)
    {
        $loaderSetter = new PhpMethod();
        $loaderSetter
                ->setName($generator->getSetterName($aspect))
                ->setVisibility(PhpMethod::VISIBILITY_PUBLIC)
                ->setBody($generator->getSetterCode($aspect))
                ->addParameter($this->generateAspectParameter($aspect))
        ;
        return $loaderSetter;
    }

    /**
     * Create a getter for the subject class name
     *
     * @return \CG\Generator\PhpMethod
     */
    protected function generateSubjectClassGetter()
    {
        $getter = new PhpMethod();
        $getter
                ->setName('getSubjectClass')
                ->setVisibility(PhpMethod::VISIBILITY_PUBLIC)
                ->setBody('return $this->_subjectClass;')
        ;
        return $getter;
    }

    /**
     * Generate a parameter for a method
     *
     * @param string $name
     * @param string|null $type
     * @return \CG\Generator\PhpParameter
     */
    protected function generateParameter($name, $type = null)
    {
        $param = new PhpParameter();
        $param->setName($name);
        if($type) {
            $param->setType($type);
        }
        return $param;
    }

    /**
     * Generate a parameter for a method expecting an aspect
     *
     * @param string $aspect
     * @return \CG\Generator\PhpParameter
     */
    protected function generateAspectParameter($aspect)
    {
        return $this->generateParameter('aspect' . ucfirst($aspect), 'PUGX\AOP\Aspect\AspectInterface');
    }

    /**
     * Generate the trigger of an aspect with the received annotation.
     * If the aspect execution returns a not null value, the generated method should break the
     * aspect execution chain and return the received value
     *
     * @param string $when
     * @param \PUGX\AOP\Aspect\BaseAnnotation $annotation
     * @param \PUGX\AOP\AspectCodeGenerator $generator
     * @return string
     */
    protected function generateAspectCode($when, BaseAnnotation $annotation, AspectCodeGenerator $generator)
    {
        $interceptorCode = '';
        if ($annotation->isTriggeredAt($when)) {
            $this->markAspectAsRequired($annotation);

            $interceptorCode = $generator->generateAspectCode(
                    $annotation->getAspectName(), get_class($annotation),
                    implode(', ', $this->getAnnotationParameters($annotation)),
                    $annotation->getParameterToInspect()
            );
        }

        return $interceptorCode;
    }

    /**
     * Get the parameters from the annotation as an array of strings
     *
     * @param \PUGX\AOP\Aspect\BaseAnnotation $annotation
     * @return array
     */
    protected function getAnnotationParameters(BaseAnnotation $annotation)
    {
        $refAnnotation = new ReflectionClass($annotation);
        $data = array();
        foreach ($refAnnotation->getProperties() as $property) {
            if ($property->isPublic()) {
                $value = $property->getValue($annotation);
                if (is_array($value)) {
                    $value = array_map(function($val) {
                                return sprintf('\'%s\'', $val);
                            }, $value);
                    $data[] = sprintf("'%s' => array(%s)", $property->name, implode(', ', $value));
                } else if (null !== $value) {
                    $data[] = sprintf("'%s' => '%s'", $property->name, $value);
                }
            }
        }
        return $data;
    }

    /**
     * Get the implementation of all the required aspects for a method
     *
     * @param ReflectionMethod $method
     * @param \PUGX\AOP\AspectCodeGenerator $generator
     * @return array
     */
    protected function getImplementedAspects(ReflectionMethod $method, AspectCodeGenerator $generator)
    {
        $annotations = $this->getFilteredAnnotations($method);
        $before = $after = $validation = array();
        foreach ($annotations as $annotation) {
            /** @var BaseAnnotation $annotation */
            $before[] = $this->generateAspectCode(BaseAnnotation::START, $annotation, $generator);
            $after[] = $this->generateAspectCode(BaseAnnotation::END, $annotation, $generator);
            $validation[] = $this->generateAspectCode(BaseAnnotation::VALIDATION, $annotation, $generator);
        }
        return array(AspectedMethod::BEFORE => $before, AspectedMethod::AFTER => $after, AspectedMethod::VALIDATION => $validation);
    }

    protected function generateDocBlock(ReflectionMethod $method, AspectCodeGenerator $generator)
    {
        $docBlock = '';
        $docLines = $generator->generateDocBlockLines($this->getFilteredAnnotations($method, false));
        if (count($docLines)) {
            $docBlock = "/**\n" . implode("\n", $docLines) . "\n */\n";
        }
        return $docBlock;
    }

    /**
     * Filters all the annotations for the current aspect.
     * This is a convenient method since you might end up having different
     * annotations in a method and when an aspect is processing a method it only
     * wants to deal with the annotations that are meaningful to it.
     *
     * @param ReflectionMethod $refMethod
     * @return array
     */
    protected function getFilteredAnnotations(ReflectionMethod $refMethod, $aspected = true)
    {
        $annotations = $this->getAnnotationsReader()->getMethodAnnotations($refMethod);
        $annotationsClass = $this->getAnnotationsClass();

        foreach ($annotations as $key => $annotation) {
            if (($annotation instanceOf $annotationsClass) != $aspected) {
                unset($annotations[$key]);
            }
        }

        return $annotations;
    }

    /**
     * Returns the annotations reader.
     * 
     * @return Reader
     */
    protected function getAnnotationsReader()
    {
        return $this->annotationsReader;
    }

    /**
     * Adds the aspect name into the list of required aspects
     *
     * @param \PUGX\AOP\Aspect\BaseAnnotation $annotation
     */
    protected function markAspectAsRequired(BaseAnnotation $annotation)
    {
        $this->requiredAspects[$annotation->getAspectName()] = true;
    }

    /**
     * get the list of the required aspects
     *
     * @return type
     */
    protected function getRequiredAspects()
    {
        return array_keys($this->requiredAspects);
    }

}
