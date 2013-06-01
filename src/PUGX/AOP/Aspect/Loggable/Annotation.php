<?php

namespace PUGX\AOP\Aspect\Loggable;

use Doctrine\Common\Annotations\Annotation as BaseAnnotation;
use Psr\Log\LogLevel;

/**
 * Annotation class for the Loggable aspect.
 * 
 * @Annotation
 */
class Annotation extends BaseAnnotation
{
    public $what;
    public $when;
    public $with;
    public $as;
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
}