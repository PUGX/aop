<?php

namespace PUGX\AOP\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use \PHPUnit_Framework_TestCase as TestCase;
use Pimple;
use PUGX\AOP\DependencyInjection\Compiler\Symfony2;
use PUGX\AOP\DependencyInjection\CompilerResolver;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class MainTest extends TestCase
{
    protected $container;
    
    public function setUp()
    {
        parent::setUp();

        $this->container = new ContainerBuilder();

        $loader = new YamlFileLoader($this->container, new FileLocator(__DIR__));
        $loader->load(TEST_BASE_DIR . '/container.yml');
    }
    
    public function testSimpleLogging()
    {
        $proxyDir = TEST_BASE_DIR . "/proxy/";

        $this->container->addCompilerPass(new Symfony2(new AnnotationReader(), $proxyDir, '\PUGX\AOP\Aspect\BaseAnnotation', array('loggable', 'roulette')));
        $this->container->compile();

        $service = $this->container->get('my_service');
        $this->assertNotEquals("PUGX\\AOP\\Stub\\MyClass", get_class($service));
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Random error dispatched from Roulette aspect
     */
    public function testSimpleRoulette()
    {
        $proxyDir = TEST_BASE_DIR . "/proxy/";

        $this->container->addCompilerPass(new Symfony2(new AnnotationReader(), $proxyDir, '\PUGX\AOP\Aspect\BaseAnnotation', array('loggable', 'roulette')));
        $this->container->compile();

        $service = $this->container->get('my_service');
        $this->assertNotEquals("PUGX\\AOP\\Stub\\MyClass", get_class($service));

        $service->randomError(new \stdClass);
    }
}

