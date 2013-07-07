<?php

namespace PUGX\AOP\Aspect\Validator;

use PUGX\AOP\Aspect\BaseAnnotation;

/**
 * Annotation class for the Loggable aspect.
 * 
 * @Annotation
 */
class Annotation extends BaseAnnotation
{

    const POSITIVE = 0;
    const NEGATIVE = 1;
    const ZERO = 2;
    const NULL = 3;
    const NOT_NULL = 4;

    protected $_aspectName = 'Validator';
    public $when = self::VALIDATION;
    public $parameter = '';
    public $value = self::NOT_NULL;

    /**
     * Get the name of the parameter to inspect
     *
     * @return string
     */
    public function getParameterToInspect()
    {
        return $this->parameter;
    }

}
