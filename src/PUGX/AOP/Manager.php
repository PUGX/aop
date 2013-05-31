<?php

namespace PUGX\AOP;

use PUGX\AOP\DependencyInjection\CompilerResolver;

/**
 * Main class that handles AOP.
 * The manager is responsible for gathering aspects and triggering the
 * recompilation of the DIC.
 */
class Manager
{
    protected $aspects = array(
        'PUGX\AOP\Aspect\Loggable',
    );
    
    protected $proxyDirectory;
    
    /**
     * Constructor
     * 
     * @param array $aspects
     * @param string $proxyDirectory
     */
    public function __construct(array $aspects = null, $proxyDirectory = null)
    {
        $this->proxyDirectory   = $proxyDirectory ?: $this->getDefaultProxyDirectory();
        $this->aspects          = $aspects ?: $this->getDefaultAspects();
    }
    
    /**
     * Triggers the enabling of Aspect-Oriented programming in the given
     * $container.
     * 
     * @param object $container
     */
    public function enableAop($container)
    {
        $compiler = new CompilerResolver();
        $compiler->compileContainer($container, $this->getAspects());
    }
    
    /**
     * Returns the aspects registered with this manager.
     * 
     * @return array
     */
    protected function getAspects()
    {
        return $this->aspects;
    }
    
    /**
     * Returns an array of default aspects provided by PUGX\AOP.
     * 
     * @return array
     */
    protected function getDefaultAspects()
    {
        $aspects = array();
        
        foreach ($this->aspects as $aspect) {
            $aspects[] =  new $aspect($this->getProxyDirectory());
        }
        
        return $aspects;
    }
    
    /**
     * Returns the directory where proxy classes will be generated.
     * Each aspect will generate its own proxy, while injecting AOP, by
     * generating a class that extends the original one, saving it into this
     * directory.
     * 
     * @return string
     */
    protected function getProxyDirectory()
    {
        return $this->proxyDirectory;
    }
    
    /**
     * Returns the default proxy directory.
     * 
     * @return string
     */
    protected function getDefaultProxyDirectory()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . "pugx-aop" . DIRECTORY_SEPARATOR;
    }
}