<?php

namespace PUGX\AOP\Test;

use \PHPUnit_Framework_TestCase as TestCase;
use Pimple;
use PUGX\AOP\DependencyInjection\CompilerResolver;
use PUGX\AOP\Manager;
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
        $loader->load(TEST_BASE_DIR . DIRECTORY_SEPARATOR .'container.yml');
    }
    
    public function testSimpleLogging()
    {        
        $manager = new Manager(null, TEST_BASE_DIR . DIRECTORY_SEPARATOR . "proxy/");
        $manager->enableAop($this->container);

        $this->assertNotEquals("PUGX\AOP\Stub\MyClass", get_class($this->container->get('my_service')));
    }
}

