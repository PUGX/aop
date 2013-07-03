<?php

namespace Example;

// import the required namespaces
use Doctrine\Common\Annotations\AnnotationReader;
use PUGX\AOP\AspectGenerator;
use PUGX\AOP\ProxyGenerator;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use PUGX\AOP\Aspect\Loggable\Loggable;
use Doctrine\Common\Annotations\AnnotationRegistry;

// integrate autoloading with composer and annotations mapping
$loader = require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

// instantiate the Symfony2 DIC
$container  = new ContainerBuilder();
$loader     = new YamlFileLoader($container, new FileLocator(__DIR__));
$loader->load(__DIR__ . DIRECTORY_SEPARATOR . 'container.yml');

// define a directory where the proxy classes - containing the aspects - will be generated
$proxyDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'proxy/';

require 'MyClassExample.php';

$refClass = new ReflectionClass('\Example\MyExampleClass');
$filename = $proxyDir.'/'.str_replace('\\', '-', $refClass->name).'.php';

$pg = new ProxyGenerator($refClass, array(
    new AspectGenerator(new AnnotationReader(), '\PUGX\AOP\Aspect\Loggable\Annotation'),
));
$pg->writeClass($filename);
require $filename;

$proxyClass = $pg->getClassName($refClass);
$proxy = new $proxyClass(1, 2, new Loggable($container));
$proxy->doSomething(5);
