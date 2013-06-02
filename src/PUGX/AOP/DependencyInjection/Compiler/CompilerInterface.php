<?php

namespace PUGX\AOP\DependencyInjection\Compiler;

/**
 * Interface for containers' compilers.
 * 
 * If you want to add AOP support for a dependency injection container you need
 * to create a new compiler that implements this interface.
 */
interface CompilerInterface
{
    /**
     * Analyzes the given $container and recompiles it after processing all the
     * services that need injection of aspects.
     * This function is basically responsible for changing the class name of a
     * service with the generated proxy class that contains all the injected
     * aspects.
     * 
     * @param array $aspects
     */
    public function compile(array $aspects);
    
    /**
     * Adds to the $serviceId of the container managed by the compiler the
     * argument $name, with the service identified by $name of the container
     * itself.
     * Calling CompilerInterface::addArgument('my_class', 'logger') will result
     * in adding to the 'my_class' service the argument 'logger', which is
     * the service 'logger' in the container.
     * 
     * @param string $serviceId
     * @param string $service
     */
    public function addArgument($serviceId, $service);
}