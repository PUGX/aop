<?php

namespace PUGX\AOP\Aspect\Loggable;

use PUGX\AOP\Aspect\BaseAnnotation;
use Psr\Log\LogLevel;

/**
 * Annotation class for the Loggable aspect.
 * 
 * @Annotation
 */
class Annotation extends BaseAnnotation
{
    public $what;
    public $with;
    public $as;
    public $context = array();
    public $level = LogLevel::INFO;

    /**
     * Returns all the variables that should be logged as context in the scope
     * of this Loggable annotation.
     * 
     * @return array
     */
    public function getContextParameters()
    {
        if ($this->context) {
            return explode(',', $this->context);
        }
        
        return '';
    }
}
