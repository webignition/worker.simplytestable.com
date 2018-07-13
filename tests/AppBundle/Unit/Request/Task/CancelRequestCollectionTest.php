<?php

namespace Tests\AppBundle\Unit\Request\Task;

use AppBundle\Request\Task\CancelRequest;
use AppBundle\Request\Task\CancelRequestCollection;

class CancelRequestCollectionTest extends \PHPUnit\Framework\TestCase
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
