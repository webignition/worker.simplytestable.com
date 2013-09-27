<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\CssValidation;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class PerformCommandCssValidationTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'CSS validation';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
}
