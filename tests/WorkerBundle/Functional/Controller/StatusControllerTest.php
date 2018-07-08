<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\StatusController;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;

class StatusControllerTest extends AbstractBaseTestCase
{
    public function testIndexAction()
    {
        $statusController = new StatusController();

        $response = $statusController->indexAction(
            self::$container->get(WorkerService::class),
            self::$container->get(HttpCache::class)
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}
