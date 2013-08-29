<?php
define('TEST_BASE_DIR', __DIR__);
$proxyDir = __DIR__ . DIRECTORY_SEPARATOR . 'proxy';

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('PUGX\AOPTest', __DIR__);
$loader->add('PUGX\AOP\Test', __DIR__);
$loader->add('PUGX\AOP\Stub', __DIR__);
$loader->add('PUGX\AOP\Proxy', $proxyDir);

use Doctrine\Common\Annotations\AnnotationRegistry;
use PUGX\AOP\Autoload\Composer as Autoloader;
use PUGX\AOP\Aspect\Loggable;

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

shell_exec(sprintf("rm -rf %c%s*", DIRECTORY_SEPARATOR, $proxyDir));
