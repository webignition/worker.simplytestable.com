<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Controller;

use Doctrine\Common\Cache\MemcacheCache;
use SimplyTestable\WorkerBundle\Controller\StatusController;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;

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
        $response = $this->createStatusController()->indexAction();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testIndexActionHttpCacheAllHitsNoMisses()
    {
        $memcacheCache = \Mockery::mock(MemcacheCache::class);
        $memcacheCache
            ->shouldReceive('getStats')
            ->andReturn([
                'hits' => 1,
                'misses' => 0,
            ]);

        $this->container->get('simplytestable.services.httpclientservice')
            ->shouldReceive('getMemcacheCache')
            ->andReturn($memcacheCache);

        $response = $this->createStatusController()->indexAction();
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @return StatusController
     */
    private function createStatusController()
    {
        $controller = new StatusController();
        $controller->setContainer($this->container);

        return $controller;
    }
}
