<?php

namespace PUGX\AOP\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use PUGX\AOP\DependencyInjection\Compiler as BaseCompiler;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Compiled for the Symfony2 dependency injection container.
 */
class Symfony2 extends BaseCompiler implements CompilerInterface
{
    /**
     * Compiles all the service's definitions.
     * 
     * @param array $aspects
     */
    public function compile(array $aspects)
    {
        $container = $this->getContainer();
        
        foreach ($container->getDefinitions() as $id => $definition) {
            $this->compileDefinition($id, $definition, $aspects);
        }
    }
    
    /**
     * Compiles the $id service's $definition with all the aspects provided.
     * 
     * @param string $id
     * @param \Symfony\Component\DependencyInjection\Definition $definition
     * @param array $aspects
     */
    protected function compileDefinition($id, Definition $definition, array $aspects)
    {        
        foreach ($aspects as $aspect) {
            $definition->setClass($aspect->attach($definition->getClass(), $this->getService($id)));
        }
    }
    
    /**
     * Adds the service $name to the service $serviceId as an argument.
     * If the service is an object, it gets automatically added to the
     * definition, if it is a string, it means that it is a reference to another
     * service in the container itself, so it's retrieved from the container.
     * 
     * @param string $serviceId
     * @param string $service
     */
    public function addArgument($serviceId, $service)
    {
        $definition = $this->getContainer()->getDefinition($serviceId);
        $arguments  = $definition->getArguments();
        
        if (is_object($service)) {
            $serviceName    = $service->getAspectId();
        } else {
            $serviceName    = $service;
            $service        = $this->getContainer()->get($service);
        }
        
        $definition->setArguments(array_merge($arguments, array(
            $serviceName => $service
        )));
    }
}