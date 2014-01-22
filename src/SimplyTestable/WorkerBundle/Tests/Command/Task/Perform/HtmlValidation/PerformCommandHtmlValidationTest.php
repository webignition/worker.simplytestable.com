<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class PerformCommandHtmlValidationTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'HTML validation';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
    
}
