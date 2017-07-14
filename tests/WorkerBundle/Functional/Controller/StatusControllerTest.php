<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\StatusController;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class StatusControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function getServicesToMock()
    {
        return [
            'simplytestable.services.httpclientservice',
        ];
    }

    public function testIndexAction()
    {
        $statusController = new StatusController();

        $response = $statusController->indexAction(
            $this->container->get(WorkerService::class),
            $this->container->get(HttpCache::class)
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}
