<?php

namespace PUGX\AOP\Aspect;

/**
 * This class identifies a dependency that an aspect injects into a service
 * while overwriting it.
 */
class Dependency
{
    protected $type;
    protected $name;
    
    /**
     * Constructor
     * 
     * @param string $name
     * @param string $type
     */
    public function __construct($name, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }
    
    /**
     * Returns the name of the dependency.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Returns the type of the dependency (ie 'Psr\Log\LoggerInterface',
     * or 'array').
     * 
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}