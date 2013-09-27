<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\JsStaticAnalysis;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class PerformJsStaticAnalysisTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'JS static analysis';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
    
}
