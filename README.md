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

* support dynamic aspects (**not** with code-generation)
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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use PUGX\AOP\Aspect\Loggable;
use PUGX\AOP\Manager;
use Doctrine\Common\Annotations\AnnotationRegistry;

// integrate autoloading (composer is recommended) and annotations mapping
$loader = require '/path/to/my/autoload/script.php';
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

// instantiate the Symfony2 DIC
$container  = new ContainerBuilder();
$loader     = new YamlFileLoader($container, new FileLocator(__DIR__));
$loader->load(__DIR__ . DIRECTORY_SEPARATOR . 'container.yml');

// define a directory where the proxy classes - containing the aspects - will be generated
$proxyDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'proxy/';

// instantiate the AOP manager with an array of aspects
$manager = new Manager(array(), $proxyDir);

// tell the manager to inject AOP in the container
$manager->enableAop($container);
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

// import the Loggable aspect as Log
use PUGX\AOP\Aspect\Loggable\Annotation as Log;

// instantiate the AOP manager, adding the Loggable aspect
$manager = new Manager(array(
    new Loggable($proxyDir)
), $proxyDir);
```

Now, let's see how the DIC is configured:

``` yaml
services:
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
     * @Log(what="$this->b", with="monolog.logger_standard", as="Log with context!", context="$this->b")
     * @Log(what="$this->b", when="end", with="monolog.logger_standard", as="HOLY COW! Now MyExampleClass::b is %s")
     */
    public function doSomething()
    {
        $this ->b = $this->b * 10;
    }
}
```

At the end, you just need to run the code
to see that `Monolog` is actually logging
thanks to the Loggable aspect:

``` php
<?php

$myExampleService = $container->get('my_example_service');
$myExampleService->doSomething();
```

