<?php

namespace PUGX\AOP\DependencyInjection\Exception;

use PUGX\AOP\Exception;

/**
 * Exception triggered when the CompilerResolver is asked to compile a container
 * but we don't have a specific Compiler for it.
 */
class UnsupportedContainer extends Exception
{
    const MESSAGE = "The container %s is currently not supported by PUGX\AOP";
    
    /**
     * Constructor
     * 
     * @param object $container
     */
    public function __construct($container)
    {
        $this->message = sprintf(self::MESSAGE, get_class($container));
    }
}