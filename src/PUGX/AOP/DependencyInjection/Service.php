<?php

namespace PUGX\AOP\DependencyInjection;

use PUGX\AOP\DependencyInjection\Compiler;

/**
 * This class represents a service of a container.
 */
class Service
{
    protected $id;
    protected $compiler;
    
    /**
     * Constructor
     * 
     * @param string $id
     * @param \PUGX\AOP\DependencyInjection\Compiler $compiler
     */
    public function __construct($id, Compiler $compiler)
    {
        $this->id       = $id;
        $this->compiler = $compiler;
    }
    
    /**
     * Adds an argument to the service represented by this object.
     * 
     * @param string $serviceId
     */
    public function addArgument($serviceId)
    {
        $this->getCompiler()->addArgument($this->getId(), $serviceId);
    }
    
    /**
     * Returns the compiler instance associated to this service.
     * The compiler is needed to have a reference from the service itself to
     * the container.
     * 
     * @return PUGX\AOP\DependencyInjection\Compiler
     */
    protected function getCompiler()
    {
        return $this->compiler;
    }
    
    /**
     * Returns the identifier of this service.
     * 
     * @return string
     */
    protected function getId()
    {
        return $this->id;
    }
}