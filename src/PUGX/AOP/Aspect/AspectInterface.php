<?php

namespace PUGX\AOP\Aspect;

/**
 * Interface that aspects must implement.
 */
interface AspectInterface
{

    /**
     * Triggers AOP invokation.
     * Aspects use this method to do what they want taking advantage of:
     * - the $stage (start / end)
     * - the pure instance of the AOP-injected $service
     * - the $refService, a reflection instance of the service where they are injected into
     * - the $refParameters passed to the method which has AOP implemented with annotations
     * - the list of $arguments' values passed to that method
     *
     * @param BaseAnnotation $annotation
     * @param $instance
     * @param $methodName
     * @param array $arguments
     * @return void
     */
    public function trigger(BaseAnnotation $annotation, $instance, $methodName, array $arguments);
}
