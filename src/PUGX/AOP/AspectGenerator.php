<?php

namespace PUGX\AOP;

use CG\Core\ClassUtils;
use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Generator\PhpProperty;
use CG\Generator\Writer;
use CG\Proxy\GeneratorInterface;
use Doctrine\Common\Annotations\Reader;
use PUGX\AOP\Aspect\Loggable\Annotation;
use ReflectionClass;
use Doctrine\Common\Annotations\AnnotationRegistry;
use ReflectionMethod;
use PUGX\AOP\DependencyInjection\Compiler;

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
        $methods = $this->getMethods($originalClass);

        $interceptorLoader = new PhpProperty();
        $interceptorLoader
            ->setName($this->prefix.'aspect')
            ->setVisibility(PhpProperty::VISIBILITY_PRIVATE)
        ;
        $generatedClass->setProperty($interceptorLoader);

        $loaderSetter = new PhpMethod();
        $loaderSetter
            ->setName($this->prefix.'setAspect')
            ->setVisibility(PhpMethod::VISIBILITY_PUBLIC)
            ->setBody('$this->'.$this->prefix.'aspect = $aspect;')
        ;
        $generatedClass->setMethod($loaderSetter);

        $loaderParam = new PhpParameter();
        $loaderParam
            ->setName('aspect')
            ->setType('PUGX\AOP\Aspect\AspectInterface')
        ;
        $loaderSetter->addParameter($loaderParam);

        /** @var ReflectionMethod $method */
        foreach ($methods as $method) {
            $params = array();
            foreach ($method->getParameters() as $param) {
                $params[] = '$'.$param->name;
            }
            $params = implode(', ', $params);
            $annotations = $this->getAspectAnnotations($method);
            $before = array();
            $after  = array();
            foreach($annotations as $annotation) {
                /** @var Annotation $annotation */
                $before[] = $this->generateAspectCode($method->name, Annotation::START, $annotation, $params);

                $after[] = $this->generateAspectCode($method->name, Annotation::END, $annotation, $params);
            }

            $interceptorCode = sprintf('$reflection = new \ReflectionMethod(%s, %s);'."\n",
                                       var_export(ClassUtils::getUserClass($method->class), true),
                                       var_export($method->name, true)
            );

            if ($method->name === '__construct') {
                $interceptorCode .= '$this->'.$this->prefix.'aspect = $aspect;'."\n";
            }

            foreach($before as $code) {
                $interceptorCode .= $code;
            }
            $interceptorCode .= "\n";
            $interceptorCode .= sprintf('$return = $reflection->invokeArgs($this, array(%s));'."\n\n", $params);
            foreach($after as $code) {
                $interceptorCode .= $code;
            }
            $interceptorCode .= "\n";
            $interceptorCode .= 'return $return;';
            $genMethod = PhpMethod::fromReflection($method)
                ->setBody($interceptorCode)
                ->setDocblock(null)
            ;
            if ($method->name === '__construct') {
                $genMethod->addParameter($loaderParam);
            }

            $generatedClass->setMethod($genMethod);
        }
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
            function($method) { return !$method->isFinal() && !$method->isStatic(); }
        );
    }

    /**
     * @param $name
     * @param $when
     * @param \PUGX\AOP\Aspect\BaseAnnotation $annotation
     * @param $params
     * @return string
     */
    protected function generateAspectCode($name, $when, \PUGX\AOP\Aspect\BaseAnnotation $annotation, $params)
    {
        $interceptorCode = '';
        if ($annotation->isTriggeredAt($when)) {
            $refAnnotation = new ReflectionClass($annotation);
            $data = array();
            foreach($refAnnotation->getProperties() as $property) {
                $value  = $property->getValue($annotation);
                if (is_array($value)) {
                    $value = array_map(function($val) {
                        return sprintf('\'%s\'', $val);
                    }, $value);
                    $data[] = sprintf("'%s' => array(%s)", $property->name, implode(', ', $value));

                }
                else if (null !== $value) {
                    $data[] = sprintf("'%s' => '%s'", $property->name, $value);
                }
            }
            $writer = new Writer();
            $writer->writeln(sprintf('$'.$this->prefix.'annotation = new '.$this->getAnnotationsClass().'(array(%s));', implode(', ', $data)));
            $interceptorCode .= $writer->getContent();
            $interceptorCode .= sprintf('$this->%saspect->trigger($%sannotation, $this, \'%s\', array(%s));' . "\n",
                                        $this->prefix, $this->prefix, $name, $params);
        }

        return $interceptorCode;
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
        $annotations        = $this->getAnnotationsReader()->getMethodAnnotations($refMethod);
        $annotationsClass   = $this->getAnnotationsClass();

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
}
