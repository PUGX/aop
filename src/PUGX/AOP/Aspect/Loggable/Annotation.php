<?php

namespace PUGX\AOP\Aspect\Loggable;

use PUGX\AOP\Aspect\Annotation as BaseAnnotation;
use Psr\Log\LogLevel;

/**
 * Annotation class for the Loggable aspect.
 * 
 * @Annotation
 */
class Annotation extends BaseAnnotation
{
    public $what;
    public $when = BaseAnnotation::START;
    public $with;
    public $as;
    public $context;
    public $level = LogLevel::INFO;
    
    /**
     * Checks whether the annotation is meant to add logging at the given
     * $stage.
     * 
     * @param string $stage
     * @return bool
     */
    public function shouldLogAt($stage)
    {
        return $this->when === $stage;
    }
    
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
        
        return array();
    }
}
