# PUGX/AOP

[![Build Status](https://travis-ci.org/PUGX/aop.png?branch=master)](https://travis-ci.org/PUGX/aop)

This library aims to provide a very simple
and lightweight support for Aspect-Oriented
Programming in PHP frameworks and libraries.

It basically provides a layer to compile
aspects in classes exposed as services in a
dependency injection container.

## Development

AOP is currently under development, so it
is not recommended for production environments.

In the pipeline, we have the following ideas:

* evaluate how to integrate with the code/concepts provided in [JMSAopBundle](https://github.com/schmittjoh/JMSAopBundle)
* support static compilation of the container (for example, before deployments)
* caching
* covering the library with more and more unit tests
* writing compilers for more DICs

This library is **experimental**: we need to get our heads around the
idea of AOP implemented in this way before going further with it.
Development, in any case, is active.

## Supported containers

Here is a list of the supported DICs:

* [Symfony2](https://github.com/symfony/DependencyInjection)

## Available aspects

Here is a list of available aspects:

* [Loggable](https://github.com/odino/aop/blob/master/src/PUGX/AOP/Aspect/Loggable/Annotation.php)

## Installation

Installation should be done via composer.

Here's the link of the [package on Packagist](https://packagist.org/packages/pugx/aop).

## Examples

Here is a brief recap of how to integrate AOP in your
own code - you can also have a look at the scripts
provided in the `examples` directory.


``` php
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

$symfony2Compiler = new Symfony2(new AnnotationReader(), $proxyDir, '\PUGX\AOP\Aspect\BaseAnnotation', array());
$container->addCompilerPass($symfony2Compiler);
$container->compile();
```

That's it, now you have successfully compiled
your DIC with AOP.

As you probably noticed, we passed an empty array
representing the list of aspects. Read on to understand
how to actually inject aspects in the container's services.

### Loggable


Here is how you enable the `Loggable` aspect:

``` php
<?php

// Enable the Loggable aspect for all classes in the container that has @Log annotation
$symfony2Compiler = new Symfony2(new AnnotationReader(), $proxyDir, '\PUGX\AOP\Aspect\BaseAnnotation', array('loggable'));
$container->addCompilerPass($symfony2Compiler);
$container->compile();
```

Now, let's see how the DIC is configured:

``` yaml
services:
  loggable:
    class: "PUGX\\AOP\\Aspect\\Loggable\\Loggable"
    arguments: ["@service_container"]
  monolog.logger_standard:
    class: "Monolog\\Logger"
    arguments:
      name: logger
  my_example_service:
    class: "Example\\MyExampleClass"
    arguments:
      a: 1
      b: 2
```

At this point you just need to add the annotations
to enable Loggable in the service `my_example_service`:

``` php
<?php

namespace Example;

// import the Loggable aspect as Log
use PUGX\AOP\Aspect\Loggable\Annotation as Log;

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
     * @Log(what="$c", when="start", with="monolog.logger_standard", as="argument $c is %s")
     * @Log(what="$this->b", when="start", with="monolog.logger_standard", as="Hey, value of MyExampleClass::b is %s")
     * @Log(what="$this->b", when="end", with="monolog.logger_standard", as="HOLY COW! Now MyExampleClass::b is %s")
     * @\PUGX\AOP\Stub\MyAnnotation
     */
    public function doSomething($c)
    {
        $this->b = $this->b * 10 + (int) $c;
    }
}
```

At the end, you just need to run the code
to see that `Monolog` is actually logging
thanks to the Loggable aspect:

``` php
<?php

$myExampleService = $container->get('my_example_service');
$myExampleService->doSomething(5);
```

