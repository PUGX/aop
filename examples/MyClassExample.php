<?php

namespace Example;

// import the Loggable aspect as Log
use PUGX\AOP\Aspect\Loggable\Annotation as Log;
use PUGX\AOP\Aspect\Roulette\Annotation as Roulette;

/**
 * now, lets say that I have this class, I enabled Loggable in the constructor and
 * the doSomething method
 *
 * @\PUGX\AOP\Stub\MyAnnotation
 * @link https://github.com/PUGX/aop the aop project homepage
 */
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

    /**
     * @Roulette
     */
    public function doSomethingSometimes($c)
    {
        $this->a = $this->b = $c;
    }
}
