<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\BaseTest;

abstract class TaskDriverTest extends BaseTest {

    protected function getTaskTypeName() {
        return 'Link Integrity';
    }

}
