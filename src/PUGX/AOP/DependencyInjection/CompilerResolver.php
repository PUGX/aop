<?php

namespace PUGX\AOP\DependencyInjection;

use PUGX\AOP\DependencyInjection\Exception\UnsupportedContainer;

/**
 * Class used to compile AOP into a container.
 */
class CompilerResolver
{
    protected $compilers = array(
        'Symfony\Component\DependencyInjection\ContainerBuilder' => 'Symfony2',
    );
    
    /**
     * Compiles a container.
     * This method, actually, will first check if the container is supported
     * and will then delegate the compilation to a specific compiler class
     * (@see PUGX\AOP\DependencyInjection\Compiler or PUGX\AOP\DependencyInjection\Compiler\Symfony2).
     * 
     * @param object $container
     * @param array $aspects
     * @throws UnsupportedContainer
     */
    public function compileContainer($container, $aspects)
    {
        $compilers = $this->getCompilers();

        if (isset($compilers[get_class($container)])) {
            $compilerClass = sprintf("PUGX\AOP\DependencyInjection\Compiler\%s", $compilers[get_class($container)]);
            $compiler = new $compilerClass($container);
            
            $compiler->compile($aspects);
            
            return true;
        }
        
        throw new UnsupportedContainer($container);
    }
    
    /**
     * Returns the compilers available for DICs.
     * 
     * @return array
     */
    protected function getCompilers()
    {
        return $this->compilers;
    }
}
