<?php

namespace PUGX\AOP\Aspect\Roulette;

use PUGX\AOP\Aspect\BaseAnnotation;

/**
 * Annotation class for the Roulette aspect.
 * 
 * @Annotation
 */
class Annotation extends BaseAnnotation
{

    public $probability = 0.5;
    public $exception = 'PUGX\AOP\Exception';
    public $message = 'Random error dispatched from Roulette aspect';
    protected $_aspectName = 'Roulette';

}
