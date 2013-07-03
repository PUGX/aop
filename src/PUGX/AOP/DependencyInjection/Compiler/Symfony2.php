<?php

namespace PUGX\AOP\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use PUGX\AOP\Aspect\Loggable;
use PUGX\AOP\AspectGenerator;
use PUGX\AOP\ProxyGenerator;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PUGX\AOP\DependencyInjection\Compiler as BaseCompiler;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiled for the Symfony2 dependency injection container.
 */
class Symfony2 implements CompilerPassInterface
{

    protected $proxyDirectory;

    /**
     * @var Reader
     */
    protected $annotationReader;

    protected $annotationClassName;

    protected $serviceName;

    /**
     * Constructor
     *
     * @param Reader $annotationReader
     * @param $proxyDirectory
     * @param string $annotationClassName
     * @param string $serviceName
     * @internal param array $annotations
     */
    public function __construct(Reader $annotationReader, $proxyDirectory, $annotationClassName, $serviceName)
    {
        $this->annotationReader    = $annotationReader;
        $this->proxyDirectory      = $proxyDirectory;
        $this->serviceName         = $serviceName;
        $this->annotationClassName = $annotationClassName;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            $this->compileDefinition($id, $definition);
        }
    }

    /**
     * Compiles the $id service's $definition with all the aspects provided.
     *
     * @param string $id
     * @param \Symfony\Component\DependencyInjection\Definition $definition
     */
    protected function compileDefinition($id, Definition $definition)
    {
        $refClass = new ReflectionClass($definition->getClass());
        $aspectGenerator = new AspectGenerator($this->annotationReader, $this->annotationClassName);
        if ($aspectGenerator->hasAnnotation($refClass)) {
            $pg = new ProxyGenerator($refClass, array($aspectGenerator));

            $filename = $this->proxyDirectory.'/'.str_replace('\\', '-', $refClass->name).'.php';
            $pg->writeClass($filename);

            $definition->setClass($pg->getClassName($refClass));

            $definition->setFile($filename);
            $definition->setClass($pg->getClassName($refClass));
            if(is_array($this->serviceName)){
                foreach($this->serviceName as $serviceName){
                    $definition->addArgument(new Reference($serviceName));
                }
            } else {
                    $definition->addArgument(new Reference($this->serviceName));
            }
        }
    }
}
