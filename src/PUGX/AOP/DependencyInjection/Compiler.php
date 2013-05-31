<?php

namespace PUGX\AOP\DependencyInjection;

use PUGX\AOP\DependencyInjection\Compiler\CompilerInterface;

/**
 * Abstract compiler.
 */
abstract class Compiler implements CompilerInterface
{
    /**
     * The DIC associated with this compiler.
     *
     * @var object
     */
    protected $container;
    
    /**
     * Constructor
     * 
     * @param object $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }
    
    /**
     * Returns a reference to the service $id for later update.
     * This is need so that aspects can tell this service instance to add an
     * argument to the service itself - if needed - through the compiler, which
     * has access to the container.
     * 
     * @param string $id
     * @return \PUGX\AOP\DependencyInjection\Service
     */
    protected function getService($id)
    {
        return new Service($id, $this);
    }
    
    /**
     * Returns the DIC that this compiler will compile.
     * 
     * @return object
     */
    public function getContainer()
    {
        return $this->container;
    }
    
    /**
     * @inheritdoc
     */
    abstract public function compile(array $aspects);
    
    /**
     * @inheritdoc
     */
    abstract public function addArgument($serviceId, $name);
}