<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;

use Doctrine\Common\Cache\MemcacheCache;
use SimplyTestable\WorkerBundle\Controller\StatusController;

class StatusControllerTest extends BaseControllerJsonTestCase
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
        $response = $this->getStatusController('indexAction')->indexAction();
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

        $response = $this->getStatusController('indexAction')->indexAction();
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     *
     * @param string $methodName
     * @param array $postData
     *
     * @return StatusController
     */
    private function getStatusController($methodName, $postData = [])
    {
        /* @var StatusController $statusController */
        $statusController = $this->getController(StatusController::class, $methodName, $postData);

        return $statusController;
    }
}
