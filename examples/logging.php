<?php

namespace Example;

// import the required namespaces
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use PUGX\AOP\Aspect\Loggable;
use PUGX\AOP\Manager;
use Doctrine\Common\Annotations\AnnotationRegistry;

// integrate autoloading with composer and annotations mapping
$loader = require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

// import the Loggable aspect as Log
use PUGX\AOP\Aspect\Loggable\Annotation as Log;

// instantiate the Symfony2 DIC
$container  = new ContainerBuilder();
$loader     = new YamlFileLoader($container, new FileLocator(__DIR__));
$loader->load(__DIR__ . DIRECTORY_SEPARATOR . 'container.yml');

// define a directory where the proxy classes - containing the aspects - will be generated
$proxyDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'proxy/';

// instantiate the AOP manager, adding the Loggable aspect
$manager = new Manager(array(
    new Loggable($proxyDir)
), $proxyDir);

// tell the manager to inject AOP in the container
$manager->enableAop($container);

// now, lets say that I have this class, I enabled Loggable in the constructor and
// the doSomething method
class MyExampleClass
{
    protected $a;
    protected $b;
    
    /**
     * @Log(what="$a", when="start", with="monolog.logger_standard", as="Hey, Im getting %s as first argument")
     */
    public function __construct($a, $b)
    {
        $this->a = $a;
        $this->b = $b;
    }
    
    /**
     * @Log(what="$this->b", when="start", with="monolog.logger_standard", as="Hey, value of MyExampleClass::b is %s")
     * @Log(what="$this->b", when="end", with="monolog.logger_standard", as="HOLY COW! Now MyExampleClass::b is %s")
     */
    public function doSomething()
    {
        $this ->b = $this->b * 10;
    }
}

// at this point, let's simply roll some code
$myExampleService = $container->get('my_example_service');
$myExampleService->doSomething();