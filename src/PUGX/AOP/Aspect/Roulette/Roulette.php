<?php

namespace PUGX\AOP\Aspect\Roulette;

use PUGX\AOP\Aspect\AspectInterface,
    PUGX\AOP\Aspect\BaseAnnotation;

class Roulette implements AspectInterface
{

    private $container;

    function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function trigger(BaseAnnotation $annotation, $instance, $methodName, array $arguments)
    {
        if (mt_rand(0, 1000) <= $annotation->probability * 1000) {
            $exceptionClass = $annotation->exception;
            throw new $exceptionClass($annotation->message);
        }
        return null;
    }

}
