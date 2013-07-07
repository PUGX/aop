<?php

namespace PUGX\AOP\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use \PHPUnit_Framework_TestCase as TestCase;
use PUGX\AOP\DependencyInjection\Compiler\Symfony2;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class MainTest extends TestCase
{

    protected $service;

    public function setUp()
    {
        $container = new ContainerBuilder();

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load(TEST_BASE_DIR . '/container.yml');
        $proxyDir = TEST_BASE_DIR . "/proxy/";

        $container->addCompilerPass(new Symfony2(new AnnotationReader(), $proxyDir, '\PUGX\AOP\Aspect\BaseAnnotation', array('loggable', 'validator', 'roulette')));
        $container->compile();
        $this->service = $container->get('my_service');
    }

    public function testSimpleLogging()
    {
        $this->assertNotEquals("PUGX\\AOP\\Stub\\MyClass", get_class($this->service));
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Random error dispatched from Roulette aspect
     */
    public function testSimpleRoulette()
    {
        $this->service->randomError(new \stdClass);
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage The parameter [a] has an invalid value [-1]
     */
    public function testSimpleValidator()
    {
        $this->assertEquals(2, $this->service->getSquareRoot(4));
        $this->service->getSquareRoot(-1);
    }

}

