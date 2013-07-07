<?php

namespace PUGX\AOP\Aspect\Validator;

use PUGX\AOP\Aspect\AspectInterface,
    PUGX\AOP\Aspect\BaseAnnotation;
use Respect\Validation\Validator as v;

class Validator implements AspectInterface
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
        $parameterToInspect = $annotation->getParameterToInspect();
        $reflMethod = new \ReflectionMethod($instance->getSubjectClass(), $methodName);
        foreach ($reflMethod->getParameters() as $key => $parameter) {
            if ($parameter->name == $parameterToInspect) {
                return $this->validate($annotation, $arguments[$key]);
            }
        }
        throw new \Exception($parameterToInspect . ' not found!');
    }

    protected function validate($annotation, $value)
    {
        if (!$this->getValidator($annotation->value)->validate($value)) {
            throw new \Exception('The parameter [' . $annotation->getParameterToInspect() . "] has an invalid value [$value]");
        }
    }
    
    protected function getValidator($validationType)
    {
        switch($validationType){
            case Annotation::POSITIVE:
                return v::numeric()->positive();
                break;
            case Annotation::NEGATIVE:
                return v::numeric()->negative();
                break;
            case Annotation::ZERO:
                return v::numeric()->equals(0);
                break;
            case Annotation::NULL:
                return v::equals(null);
                break;
            case Annotation::NOT_NULL:
                return v::not(v::equals(null));
                break;
        }
        throw new \Exception('Validator not found!');
    }

}
