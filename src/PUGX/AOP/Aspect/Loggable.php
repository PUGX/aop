<?php

namespace PUGX\AOP\Aspect;

use PUGX\AOP\Aspect;
use ReflectionMethod;
use PUGX\AOP\Aspect\Loggable\Annotation;
use PUGX\AOP\Aspect\Dependency;

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
            'logger' => new Dependency('logger_pugx_aop', "\Psr\Log\LoggerInterface"),
        );
    }
    
    /**
     * Get the logs that should be added at the $stage of the
     * method ($refMethod), using the $loggerName to log.
     * 
     * @param string $stage
     * @param PUGX\AOP\Aspect\Reflectionmethod $refMethod
     * @param PUGX\AOP\Aspect\Dependency $logger
     * @return string
     */
    protected function getLogs($stage, Reflectionmethod $refMethod, Dependency $logger)
    {
        $logs = "";

        foreach ($this->getAspectAnnotations($refMethod) as $annotation) {
            if ($annotation->shouldLogAt($stage)) {
                $logs .= $this->generateLog($annotation, $logger);
            }
        }
        
        return $logs;
    }
    
    /**
     * Generates the logging and updated the service, requiring an additional
     * argument (the logger used by the aspect).
     * 
     * @param PUGX\AOP\Aspect\Loggable\Annotation $annotation
     * @param PUGX\AOP\Aspect\Dependency $logger
     * @return string
     */
    protected function generateLog(Annotation $annotation, Dependency $logger)
    {
        $this->getService()->addArgument($annotation->with);

        return "\$this->{$logger->getName()}->{$annotation->level}(sprintf('$annotation->as', $annotation->what));";
    }
}