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
     * 
     * @param string $serviceId
     * @param string $name
     */
    public function addArgument($serviceId, $name)
    {
        $definition = $this->getContainer()->getDefinition($serviceId);
        $arguments  = $definition->getArguments();
        $definition->setArguments(array_merge($arguments, array(
            $name => $this->getContainer()->get($name)
        )));
    }
}