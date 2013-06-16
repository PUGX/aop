<?php

namespace Example;

// import the required namespaces
use Doctrine\Common\Annotations\AnnotationReader;
use PUGX\AOP\Aspect\LoggableGenerator;
use PUGX\AOP\DependencyInjection\Compiler\Symfony2;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use PUGX\AOP\Aspect\Loggable;
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

$symfony2Compiler = new Symfony2(new AnnotationReader(), $proxyDir, '\PUGX\AOP\Aspect\Loggable\Annotation', 'loggable');
$container->addCompilerPass($symfony2Compiler);
$container->compile();

$myExampleService = $container->get('my_example_service');
$myExampleService->doSomething(5);
