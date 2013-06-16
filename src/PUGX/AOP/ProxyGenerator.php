<?php

namespace PUGX\AOP;

use CG\Core\AbstractClassGenerator;
use CG\Core\DefaultNamingStrategy;
use CG\Core\NamingStrategyInterface;
use CG\Generator\PhpClass;
use CG\Generator\Writer;

class ProxyGenerator extends AbstractClassGenerator
{
    private $generatedClass;
    private $class;
    private $aspectGenerators;

    public function __construct(\ReflectionClass $class, array $aspectGenerators = array())
    {
        $this->class = $class;
        $this->aspectGenerators = $aspectGenerators;
        $this->setNamingStrategy(new DefaultNamingStrategy('PUGX'));
    }

    /**
     * Creates a new instance  of the enhanced class.
     *
     * @param  array  $args
     * @return object
     */
    public function createInstance(array $args = array())
    {
        $generatedClass = $this->getClassName($this->class);

        if (!class_exists($generatedClass, false)) {
            eval($this->generateClass());
        }

        $ref = new \ReflectionClass($generatedClass);

        return $ref->newInstanceArgs($args);
    }

    public function writeClass($filename)
    {
        if (!is_dir($dir = dirname($filename))) {
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create directory "%s".', $dir));
            }
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException(sprintf('The directory "%s" is not writable.', $dir));
        }

        file_put_contents($filename, "<?php\n\n".$this->generateClass());
    }

    /**
     * Creates a new enhanced class
     *
     * @throws \RuntimeException
     * @return string
     */
    final public function generateClass()
    {
        static $docBlock;
        if (empty($docBlock)) {
            $writer = new Writer();
            $writer
                ->writeln('/**')
                ->writeln(' * CG library enhanced proxy class.')
                ->writeln(' *')
                ->writeln(' * This code was generated automatically by the CG library, manual changes to it')
                ->writeln(' * will be lost upon next generation.')
                ->writeln(' */')
            ;
            $docBlock = $writer->getContent();
        }

        $this->generatedClass = PhpClass::create()
            ->setDocblock($docBlock)
            ->setParentClassName($this->class->name)
        ;

        $proxyClassName = $this->getClassName($this->class);
        if (false === strpos($proxyClassName, NamingStrategyInterface::SEPARATOR)) {
            throw new \RuntimeException(sprintf('The proxy class name must be suffixed with "%s" and an optional string, but got "%s".', NamingStrategyInterface::SEPARATOR, $proxyClassName));
        }
        $this->generatedClass->setName($proxyClassName);

        foreach ($this->aspectGenerators as $generator) {
            $generator->generate($this->class, $this->generatedClass);
        }

        return $this->generateCode($this->generatedClass);
    }
}
