<?php

namespace PUGX\AOP\Aspect;

use Doctrine\Common\Annotations\Annotation as DoctrineAnnotation;

/**
 * @Annotation
 */
class BaseAnnotation extends DoctrineAnnotation
{
    const START = 'start';
    const END   = 'end';

    public $when = self::START;

    /**
     * Checks whether the annotation is meant to apply the aspect at the given
     * $stage.
     *
     * @param string $stage
     * @return bool
     */
    public function isTriggeredAt($stage)
    {
        return $this->when === $stage;
    }
}
