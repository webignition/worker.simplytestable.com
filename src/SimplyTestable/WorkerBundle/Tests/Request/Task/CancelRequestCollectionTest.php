<?php

namespace SimplyTestable\WorkerBundle\Tests\Request\Task;

use SimplyTestable\WorkerBundle\Request\Task\CancelRequest;
use SimplyTestable\WorkerBundle\Request\Task\CancelRequestCollection;

class CancelRequestCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param CancelRequest[] $cancelRequests
     * @param int $expectedCollectionCount
     */
    public function testCreate($cancelRequests, $expectedCollectionCount)
    {
        $cancelCollectionRequest = new CancelRequestCollection($cancelRequests);
        $this->assertCount($expectedCollectionCount, $cancelCollectionRequest->getCancelRequests());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'empty collection' => [
                'cancelRequests' => [],
                'expectedCollectionCount' => 0,
            ],
            'non-empty collection' => [
                'cancelRequests' => [
                    \Mockery::mock(CancelRequest::class),
                ],
                'expectedCollectionCount' => 1,
            ],
        ];
    }
}
