<?php

namespace PUGX\AOP\Aspect;

use PUGX\AOP\Aspect;
use ReflectionMethod;
use PUGX\AOP\Aspect\Annotation;
use ReflectionObject;

/**
 * Loggable aspect: this aspect is capable of injecting a logger into a class
 * and logging without the need to explicitely modify your code and pass the
 * logger instance everywhere.
 */
class Loggable extends Aspect implements AspectInterface
{    
    /**
     * @inheritdoc
     */
    public function getAspectId()
    {
        return "pugx_aop_loggable";
    }
    
    /**
     * @inheritdoc
     */
    protected function injectAspectDependencies(ReflectionMethod $refMethod)
    {    
        parent::injectAspectDependencies($refMethod);
        
        foreach ($this->getAspectAnnotations($refMethod) as $annotation) {
            $this->setDependency($annotation->with);
        }
    }
    
    /**
     * @inheritdoc
     */
    public function trigger($stage, $service, ReflectionObject $refService, ReflectionMethod $refMethod, array $refParameters, array $arguments)
    {
        foreach ($this->getAspectAnnotations($refMethod) as $annotation) {
            $logger     = $this->getDependency($annotation->with);
            $context    = $this->getContext($annotation->getContextParameters(), $refService, $refParameters, $arguments, $service);
            $what       = $this->resolveParameter($annotation->what, $refService, $refParameters, $arguments, $service);
            
            if ($annotation->shouldLogAt($stage)) {
                $logger->{$annotation->level}(sprintf($annotation->as, $what), $context);
            }
        }
    }
    
    /**
     * Resolves what parameter should be logged as defined from the annotation.
     * If the parameter is a simple variable (ie $a), it looks for the arguments
     * that were passed to the "Loggabled" method, if it looks like a class
     * member ($this->a) it uses reflection to access it.
     * 
     * @param string $parameter
     * @param object $refService
     * @param array $parameters
     * @param array $arguments
     * @param object $service
     * @return type
     */
    protected function resolveParameter($parameter, $refService, $parameters, $arguments, $service)
    {
        if (strpos($parameter, 'this->')) {
            return $this->getMember(substr($parameter, 7), $refService, $service);
        }
        
        return $this->getArgument(substr($parameter, 1), $parameters, $arguments);
    }
    
    /**
     * Retrieves the $member from the $service instance through its $refObject.
     * 
     * @param string $member
     * @param ReflectionObject $refService
     * @param object $service
     * @return mixed
     */
    protected function getMember($member, ReflectionObject $refService, $service)
    {
        $property = $refService->getProperty($member);
        $property->setAccessible(true);
        
        return $property->getValue($service); 
    }
    
    /**
     * Given a list of $arguments' values and their reflection representation
     * ($parameters), returns the value of the $parameter.
     * 
     * @param type $parameter
     * @param array $refParameters
     * @param array $arguments
     * @return array|null
     */
    protected function getArgument($parameter, array $refParameters, array $arguments)
    {
        $i = 0;
        
        foreach ($refParameters as $refParameter) {
            if ($refParameter->getName() === $parameter) {
                return $arguments[$i];
            }
            
            $i++;
        }
        
        return null;
    }
    
    protected function getContext(array $contextParameters, $refService, $refParameters, $arguments, $service)
    {
        $parameters = array();
        
        foreach ($contextParameters as $key => $parameter) {
            $parameters[$parameter] = $this->resolveParameter(trim($parameter), $refService, $refParameters, $arguments, $service);
        }
        
        return $parameters;
    }
}