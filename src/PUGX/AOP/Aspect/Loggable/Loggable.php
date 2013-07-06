<?php

namespace PUGX\AOP\Aspect\Loggable;

use ReflectionMethod;
use ReflectionObject;
use PUGX\AOP\Aspect\AspectInterface;
use PUGX\AOP\Aspect\BaseAnnotation;

/**
 * Loggable aspect: this aspect is capable of injecting a logger into a class
 * and logging without the need to explicitly modify your code and pass the
 * logger instance everywhere.
 */
class Loggable implements AspectInterface
{    

    private $container;

    function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function trigger(BaseAnnotation $annotation, $instance, $methodName, array $arguments)
    {
        $refProxyClass = new ReflectionObject($instance);
        $refMethod     = new ReflectionMethod($refProxyClass->name, $methodName);
        $context       = $this->getContext($annotation->context, $refProxyClass, $arguments, $refMethod, $instance);
        $what          = $this->resolveParameter($annotation->what, $refProxyClass, $arguments, $refMethod,
                                                 $instance);

        $service = $this->container->get($annotation->with);
        $service->{$annotation->level}(sprintf($annotation->as, $what), $context);
    }

    /**
     * Resolves what parameter should be logged as defined from the annotation.
     * If the parameter is a simple variable (ie $a), it looks for the arguments
     * that were passed to the "Loggabled" method, if it looks like a class
     * member ($this->a) it uses reflection to access it.
     *
     * @param string $parameter
     * @param \ReflectionObject $refProxyClass
     * @param array $parameters
     * @param $refMethod
     * @param object $instance
     * @return type
     */
    protected function resolveParameter($parameter, ReflectionObject $refProxyClass, $parameters, $refMethod, $instance)
    {
        if (strpos($parameter, 'this->')) {
            return $this->getMember(substr($parameter, 7), $refProxyClass, $instance);
        }

        return $this->getArgument(substr($parameter, 1), $parameters, $refMethod);
    }
    
    /**
     * Retrieves the $member from the $service instance through its $refObject.
     * 
     * @param string $member
     * @param ReflectionObject $refProxyClass
     * @param object $instance
     * @return mixed
     */
    protected function getMember($member, ReflectionObject $refProxyClass, $instance)
    {
        $property = $refProxyClass->getParentClass()->getProperty($member);
        $property->setAccessible(true);
        
        return $property->getValue($instance);
    }

    /**
     * Given a list of $arguments' values and their reflection representation
     * ($parameters), returns the value of the $parameter.
     *
     * @param type $parameter
     * @param array $refParameters
     * @param ReflectionMethod $refMethod
     * @return array|null
     */
    protected function getArgument($parameter, array $refParameters, ReflectionMethod $refMethod)
    {
        $i = 0;
        $parameters = $refMethod->getParameters();
        foreach ($parameters as $refParameter) {
            if ($refParameter->getName() === $parameter) {
                return $refParameters[$i];
            }
            
            $i++;
        }
        
        return null;
    }
    
    protected function getContext(array $contextParameters, ReflectionObject $refProxyClass, $refParameters, $refMethod, $instance)
    {
        $parameters = array();
        
        foreach ($contextParameters as $parameter) {
            $parameters[$parameter] = $this->resolveParameter($parameter, $refProxyClass, $refParameters, $refMethod, $instance);
        }
        
        return $parameters;
    }
}
