<?php

namespace PUGX\AOP\Aspect;

use PUGX\AOP\DependencyInjection\Service;
use ReflectionMethod;
use ReflectionObject;

/**
 * Interface that aspects must implement.
 */
interface AspectInterface
{
    /**
     * Enables an aspect for a $class.
     * The class is identified as a service in the DIC that is managing it, so
     * we will also update the service definition using the $service.
     * 
     * @param string $class
     * @param \PUGX\AOP\DependencyInjection\Service $service
     */
    public function attach($class, Service $service);
    
    /**
     * Triggers AOP invokation.
     * Aspects use this method to do what they want taking advantage of:
     * - the $stage (start / end)
     * - the pure instance of the AOP-injected $service
     * - the $refService, a reflection instance of the service where they are injected into
     * - the $refParameters passed to the method which has AOP implemented with annotations
     * - the list of $arguments' values passed to that method
     * 
     * @param string $stage
     * @param object $service
     * @param ReflectionObject $refService
     * @param ReflectionMethod $refMethod
     * @param array $refParameters
     * @param array $arguments
     */
    public function trigger($stage, $service, ReflectionObject $refService, ReflectionMethod $refMethod, array $refParameters, array $arguments);
}