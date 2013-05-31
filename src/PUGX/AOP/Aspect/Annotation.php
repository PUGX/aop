<?php

namespace PUGX\AOP\Aspect;

use Doctrine\Common\Annotations\Annotation as BaseAnnotation;

/**
 * @Annotation
 */
class Annotation extends BaseAnnotation
{    
    const START = 'start';
    const END   = 'end';
}
