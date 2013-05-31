<?php

$loader = require __DIR__ . "/../vendor/autoload.php";

use Doctrine\Common\Annotations\AnnotationRegistry;
use PUGX\AOP\Autoload\Composer as Autoloader;
use PUGX\AOP\Aspect\Loggable;

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

define('TEST_BASE_DIR', __DIR__);

$proxyDir = TEST_BASE_DIR . DIRECTORY_SEPARATOR . 'proxy';
shell_exec(sprintf("rm -rf %s/*", $proxyDir));