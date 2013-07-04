<?php

namespace PUGX\AOP;

/**
 * Simple encapsulation of the code blocks manipultion for the 'aspected method' generation
 *
 * @author Kpacha <kpacha666@gmail.com>
 */
class AspectedMethod
{

    const DECLARATION = 1;
    const INJECTION = 0;
    const BEFORE = 2;
    const EXECUTION = 3;
    const AFTER = 4;
    const RETURNING = 5;

    private static $stages = array(
        self::DECLARATION => 'Declaration',
        self::INJECTION => 'Injection',
        self::BEFORE => 'Before',
        self::EXECUTION => 'Execution',
        self::AFTER => 'After',
        self::RETURNING => 'Returning'
    );
    private $code = array();

    /**
     * Sort the stages by their priority (key)
     */
    public function __construct()
    {
        ksort(self::$stages);
    }

    /**
     * Add a line of code into a stage (code block)
     *
     * @param string $codeToAdd
     * @param int $stage
     */
    public function addCode($codeToAdd, $stage)
    {
        if ($codeToAdd) {
            $this->code[$stage][] = $codeToAdd;
        }
    }

    /**
     * Replace a code block with the received one
     *
     * @param string|array $codeToAdd
     * @param int $stage
     */
    public function setCode($codeToAdd, $stage)
    {
        if (!is_array($codeToAdd)) {
            $codeToAdd = array($codeToAdd);
        }
        $this->code[$stage] = array();
        foreach ($codeToAdd as $lineToAdd) {
            $this->addCode($lineToAdd, $stage);
        }
    }

    /**
     * Process all code blocks and concatenate them in a single string
     *
     * @return string
     */
    public function getMethodCode()
    {
        $methodCode = '';
        foreach (self::$stages as $stage => $stageName) {
            $methodCode .= $this->getStageCode($stage);
        }
        return $methodCode;
    }

    protected function getStageCode($stage)
    {
        $stageCode = '';
        if (isset($this->code[$stage]) && $this->code[$stage]) {
            $stageCode .= "// " . self::$stages[$stage] . " stage\n";
            $stageCode .= implode("\n", $this->code[$stage]) . "\n\n";
        }
        return $stageCode;
    }

}
