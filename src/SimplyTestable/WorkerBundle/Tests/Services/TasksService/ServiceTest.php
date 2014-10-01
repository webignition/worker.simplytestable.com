<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService;

use SimplyTestable\WorkerBundle\Tests\Services\ServiceTest as BaseServiceTest;
use SimplyTestable\WorkerBundle\Services\TasksService;

abstract class ServiceTest extends BaseServiceTest {

    /**
     * @return TasksService
     */
    protected function getService() {
        return parent::getService();
    }

}
