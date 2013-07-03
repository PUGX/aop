<?php

namespace PUGX\AOP;

use CG\Core\ClassUtils;
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
    protected $prefix = '__CG_PUGX_AOP__';
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
            $count = count($this->getAspectAnnotations($method));
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

        $generatedClass = $this->prepareAspectInjection($originalClass, $generatedClass);
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
        $genMethod = PhpMethod::fromReflection($method)
                ->setBody($this->generateMethodCode($method))
                ->setDocblock(null)
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
     * @return string
     */
    protected function generateMethodCode(ReflectionMethod $method)
    {
        $params = implode(', ', $this->getMethodParameters($method));
        $implementedAspects = $this->getImplementedAspects($method, $params);

        $interceptorCode = sprintf(
                '$reflection = new \ReflectionMethod(%s, %s);' . "\n",
                var_export(ClassUtils::getUserClass($method->class), true), var_export($method->name, true)
        );

        if ($method->name === '__construct') {
            foreach ($this->getRequiredAspects() as $aspect) {
                $interceptorCode .= $this->getSetterCode($aspect) . "\n";
            }
        }

        $interceptorCode .= $this->filterImplementedAspect($implementedAspects['before']);
        $interceptorCode .= sprintf('$return = $reflection->invokeArgs($this, array(%s));' . "\n\n", $params);
        $interceptorCode .= $this->filterImplementedAspect($implementedAspects['after']);
        $interceptorCode .= 'return $return;';

        return $interceptorCode;
    }

    /**
     * Simple validation
     * @param string $implementation
     * @return string
     */
    protected function filterImplementedAspect($implementation)
    {
        return (($implementation) ? $implementation . "\n" : "");
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
        foreach ($aspects as $aspect) {
            $generatedClass->setProperty($this->generateAspectProperty($aspect));
            $generatedClass->setMethod($this->generateAspectSetter($aspect));
        }
        return $generatedClass;
    }

    /**
     * Create a property for the received aspect name
     *
     * @param string $aspect
     * @return \CG\Generator\PhpProperty
     */
    protected function generateAspectProperty($aspect)
    {
        $interceptorLoader = new PhpProperty();
        $interceptorLoader
                ->setName($this->getAspectPropertyName($aspect))
                ->setVisibility(PhpProperty::VISIBILITY_PRIVATE)
        ;
        return $interceptorLoader;
    }

    /**
     * Create a setter for the aspect property with the received aspect name
     *
     * @param string $aspect
     * @return \CG\Generator\PhpMethod
     */
    protected function generateAspectSetter($aspect)
    {
        $loaderSetter = new PhpMethod();
        $loaderSetter
                ->setName('set' . $this->prefix . 'Aspect' . ucfirst($aspect))
                ->setVisibility(PhpMethod::VISIBILITY_PUBLIC)
                ->setBody($this->getSetterCode($aspect))
        ;

        $loaderSetter->addParameter($this->generateAspectParameter($aspect));
        return $loaderSetter;
    }

    /**
     * Generate the code for the aspect setting
     *
     * @param string $aspect
     * @return string
     */
    protected function getSetterCode($aspect)
    {
        return '$this->' . $this->getAspectPropertyName($aspect) . ' = $aspect' . ucfirst($aspect) . ';';
    }

    /**
     * Generate the name of the property containing the received aspect
     *
     * @param string $aspect
     * @return string
     */
    protected function getAspectPropertyName($aspect)
    {
        return $this->prefix . 'aspect' . $aspect;
    }

    /**
     * Generate a parameter for a method expecting an aspect
     *
     * @param string $aspect
     * @return \CG\Generator\PhpParameter
     */
    protected function generateAspectParameter($aspect)
    {
        $loaderParam = new PhpParameter();
        $loaderParam
                ->setName('aspect' . ucfirst($aspect))
                ->setType('PUGX\AOP\Aspect\AspectInterface')
        ;
        return $loaderParam;
    }

    /**
     * Generate the trigger of an aspect with the received annotation.
     * If the aspect execution returns a not null value, the generated method should break the
     * aspect execution chain and return the received value
     *
     * @param $name
     * @param $when
     * @param BaseAnnotation $annotation
     * @param $params
     * @return string
     */
    protected function generateAspectCode($name, $when, BaseAnnotation $annotation, $params)
    {
        $interceptorCode = '';
        if ($annotation->isTriggeredAt($when)) {
            $this->markAspectAsRequired($annotation);

            $interceptorCode = sprintf(
                    'if(($return = $this->%s->trigger(new \%s(array(%s)), $this, \'%s\', array(%s))) !== null) return $return;' . "\n",
                    $this->getAspectPropertyName($annotation->getAspectName()), get_class($annotation),
                    implode(', ', $this->getAnnotationParameters($annotation)), $name, $params);
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
     * @param string $params
     * @return array
     */
    protected function getImplementedAspects(ReflectionMethod $method, $params)
    {
        $annotations = $this->getAspectAnnotations($method);
        $before = $after = "";
        foreach ($annotations as $annotation) {
            /** @var BaseAnnotation $annotation */
            $before .= $this->generateAspectCode($method->name, BaseAnnotation::START, $annotation, $params);

            $after .= $this->generateAspectCode($method->name, BaseAnnotation::END, $annotation, $params);
        }
        return array('before' => $before, 'after' => $after);
    }

    /**
     * Retrieves all the annotations for the current aspect.
     * This is a convenient method since you might end up having different
     * annotations in a method and when an aspect is processing a method it only
     * wants to deal with the annotations that are meaningful to it.
     *
     * @param ReflectionMethod $refMethod
     * @return array
     */
    protected function getAspectAnnotations(ReflectionMethod $refMethod)
    {
        $annotations = $this->getAnnotationsReader()->getMethodAnnotations($refMethod);
        $annotationsClass = $this->getAnnotationsClass();

        foreach ($annotations as $key => $annotation) {
            if (!$annotation instanceOf $annotationsClass) {
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
