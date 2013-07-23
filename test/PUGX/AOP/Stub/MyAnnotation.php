<?php

namespace PUGX\AOP\Stub;

/**
 * Dummy not aspected Annotation
 *
 * @author Kpacha <kpacha666@gmail.com>
 *
 * @Annotation
 */
class MyAnnotation
{

    private $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

}
