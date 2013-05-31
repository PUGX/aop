<?php

namespace PUGX\AOP\Aspect;

use PUGX\AOP\Aspect;
use ReflectionMethod;
use PUGX\AOP\Aspect\Loggable\Annotation;

/**
 * Loggable aspect: this aspect is capable of injecting a logger into a class
 * and logging without the need to explicitely modify your code and pass the
 * logger instance everywhere.
 */
class Loggable extends Aspect implements AspectInterface
{   
    /**
     * @inheritdoc
     */
    protected function generateAspectCodeAtMethodStage($stage, ReflectionMethod $refMethod)
    {
        $dependencies = $this->getDependencies();
        
        return $this->getLogs($stage, $refMethod, $dependencies['logger']);
    }
    
    public function getDependencies()
    {
        return array(
            'logger' => 'logger_pugx_aop',
        );
    }
    
    /**
     * Get the logs that should be added at the $stage of the
     * method ($refMethod), using the $loggerName to log.
     * 
     * @param string $stage
     * @param \PUGX\AOP\Aspect\Reflectionmethod $refMethod
     * @param string $loggerName
     * @return string
     */
    protected function getLogs($stage, Reflectionmethod $refMethod, $loggerName)
    {
        $logs = "";

        foreach ($this->getAspectAnnotations($refMethod) as $annotation) {
            if ($annotation->shouldLogAt($stage)) {
                $logs .= $this->generateLog($annotation, $loggerName);
            }
        }
        
        return $logs;
    }
    
    /**
     * Generates the logging and updated the service, requiring an additional
     * argument (the logger used by the aspect).
     * 
     * @param \PUGX\AOP\Aspect\Loggable\Annotation $annotation
     * @param type $loggerName
     * @return string
     */
    protected function generateLog(Annotation $annotation, $loggerName)
    {
        $this->getService()->addArgument($annotation->with);

        return "\$this->{$loggerName}->info(sprintf('$annotation->as', $annotation->what));";
    }
}