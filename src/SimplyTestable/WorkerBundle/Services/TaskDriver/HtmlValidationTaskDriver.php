<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

class HtmlValidationTaskDriver extends TaskDriver {    
    
    public function execute(Task $task) {
        return 'html validation output';
    }
    
}