<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\BaseTest;

abstract class TaskDriverTest extends BaseTest {

    protected function getTaskTypeName() {
        return 'JS Static Analysis';
    }

}
