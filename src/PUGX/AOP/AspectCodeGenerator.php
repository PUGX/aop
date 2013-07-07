<?php

namespace PUGX\AOP;

use CG\Core\ClassUtils;
use \ReflectionClass;
use \ReflectionProperty;

/**
 * Encapsulates all the patterns for the enhaced proxy class generation
 *
 * @author Kpacha <kpacha666@gmail.com>
 */
class AspectCodeGenerator
{

    protected $prefix = '__CG_PUGX_AOP__';
    private $className;
    private $methodName;
    private $params;

    public function __construct($className, $methodName = null, $params = array(), $prefix = null)
    {
        if ($prefix) {
            $this->prefix = $prefix;
        }
        $this->className = $className;
        $this->methodName = $methodName;
        $this->params = $params;
    }

    /**
     * Set the params property
     *
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Get a simple return statement
     *
     * @return string
     */
    public function generateReturningCode()
    {
        return 'return $return;';
    }

    /**
     * Generate the code for an instantiation of a \ReflectionMethod object
     *
     * @return string
     */
    public function generateReflectionDeclarationCode()
    {
        return sprintf(
                        '$reflection = new \ReflectionMethod($this->_subjectClass, %s);',
                        var_export($this->methodName, true)
        );
    }

    /**
     * Generate the code for an invocation of a \ReflectionMethod
     * 
     * @return string
     */
    public function generateReflectionInvocationCode()
    {
        return sprintf('$return = $reflection->invokeArgs($this, array(%s));', $this->params);
    }

    /**
     * Generates the code for the aspect execution and the validation of its result
     *
     * @param string $aspectName
     * @param string $annotationClass
     * @param string $annotationParams
     * @param string|null $parameter
     * @return String
     */
    public function generateAspectCode($aspectName, $annotationClass, $annotationParams, $parameter = null)
    {
        $finalAction = ($parameter) ? '$'.$parameter.' =' : 'return';
        return sprintf(
                        'if(($result = $this->%s->trigger(new \%s(array(%s)), $this, \'%s\', array(%s))) !== null) %s $result;',
                        $this->getAspectPropertyName($aspectName), $annotationClass, $annotationParams,
                        $this->methodName, $this->params, $finalAction
        );
    }

    /**
     * Regenerates an annotation
     *
     * @param array $annotations
     * @return array
     */
    public function generateDocBlockLines($annotations)
    {
        $docLines = array();
        foreach ($annotations as $annotation) {
            $docLines[] = ' * @\\' . get_class($annotation) . '(' .
                    implode(', ', $this->getParsedAnnotationProperties($annotation)) .
                    ")";
        }
        return $docLines;
    }

    /**
     * Get a list of parsed annotation properties
     *
     * @param type $annotation
     * @return array
     */
    protected function getParsedAnnotationProperties($annotation)
    {
        $refClass = new ReflectionClass(get_class($annotation));
        $parsedProperties = array();
        foreach ($refClass->getProperties() as $annotationProperty) {
            $parsedProperties[] = $annotationProperty->name . '="' .
                    $this->getParsedAnnotationPropertyValues($annotationProperty, $annotation) . '"';
        }
        return $parsedProperties;
    }

    /**
     * Get the value of the received property as a string
     *
     * @param ReflectionProperty $annotationProperty
     * @param mixed $annotation
     * @return string
     */
    protected function getParsedAnnotationPropertyValues(ReflectionProperty $annotationProperty, $annotation)
    {
        $annotationProperty->setAccessible(true);
        $value = $annotationProperty->getValue($annotation);
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        return $value;
    }

    /**
     * Generate the code for the aspect setting
     *
     * @param string $aspect
     * @return string
     */
    public function getSetterCode($aspect)
    {
        return '$this->' . $this->getAspectPropertyName($aspect) . ' = $aspect' . ucfirst($aspect) . ';';
    }

    /**
     * Generate the name for the aspect setter
     *
     * @param string $aspect
     * @return string
     */
    public function getSetterName($aspect)
    {
        return 'set' . $this->prefix . 'Aspect' . ucfirst($aspect);
    }

    /**
     * Generate the name of the property containing the received aspect
     *
     * @param string $aspect
     * @return string
     */
    public function getAspectPropertyName($aspect)
    {
        return $this->prefix . 'aspect' . $aspect;
    }

}

