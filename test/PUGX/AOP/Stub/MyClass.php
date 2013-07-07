<?php

namespace PUGX\AOP\Stub;

use \PUGX\AOP\Aspect\Loggable\Annotation as Log;
use \PUGX\AOP\Aspect\Roulette\Annotation as Roulette;
use PUGX\AOP\Aspect\Validator\Annotation as Validator;
use \stdClass;

/**
 * Some doc comments here!
 *
 * @MyAnnotation
 * @link https://github.com/PUGX/aop the aop project homepage
 */
class MyClass
{
    protected $a;
    private $b = 10;
    
    /**
     * @Log(what="$a", when="start", with="monolog.logger_standard", as="Lets log a, which is %s")
     * @Log(what="$b", when="end", with="monolog.logger_standard", as="$b is %s", level="error")
     * @Log(what="$this->a", when="end", with="monolog.logger_standard", as="MyClass::a, which is the sum of a and b, is %s")
     * @Log(what="$this->b", when="end", with="monolog.logger_standard", as="The private property $this->b is %s")
     * @Log(what="$a", with="monolog.logger_standard", as="Logging some context", context={"$b","$a","$this->a"})
     * @Log(what="$a", when="end", with="monolog.logger_standard", as="Logging some context at the end!", context={"$this->a"})
     * @param int $a
     * @param int $b
     */
    public function __construct($a, $b)
    {
        $this->a = $a + $b;
    }

    /**
     * @Roulette(probability="1")
     * @MyAnnotation
     * @param stdClass $o
     */
    public function randomError(stdClass $o)
    {
        return $o;
    }

    /**
     * @Validator(parameter="a",value=Validator::POSITIVE)
     * @param float $a
     * @return float
     */
    public function getSquareRoot($a)
    {
        return sqrt($a);
    }
}
