<?php

namespace App\Model;

class TaskOutputValues
{
    /**
     * @var mixed
     */
    private $content;

    /**
     * @var int
     */
    private $errorCount;

    /**
     * @var int
     */
    private $warningCount;

    public function __construct($content, int $errorCount, int $warningCount)
    {
        $this->content = $content;
        $this->errorCount = $errorCount;
        $this->warningCount = $warningCount;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getWarningCount(): int
    {
        return $this->warningCount;
    }
}
