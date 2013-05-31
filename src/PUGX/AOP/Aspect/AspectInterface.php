<?php

namespace PUGX\AOP\Aspect;

use PUGX\AOP\DependencyInjection\Service;
use ReflectionMethod;

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
}