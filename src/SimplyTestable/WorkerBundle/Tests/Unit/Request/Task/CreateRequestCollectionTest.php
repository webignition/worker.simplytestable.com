<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Request\Task;

use SimplyTestable\WorkerBundle\Request\Task\CreateRequest;
use SimplyTestable\WorkerBundle\Request\Task\CreateRequestCollection;

class CreateRequestCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param CreateRequest[] $createRequests
     * @param int $expectedCollectionCount
     */
    public function testCreate($createRequests, $expectedCollectionCount)
    {
        $createCollectionRequest = new CreateRequestCollection($createRequests);
        $this->assertCount($expectedCollectionCount, $createCollectionRequest->getCreateRequests());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'empty collection' => [
                'createRequests' => [],
                'expectedCollectionCount' => 0,
            ],
            'non-empty collection' => [
                'createRequests' => [
                    \Mockery::mock(CreateRequest::class),
                ],
                'expectedCollectionCount' => 1,
            ],
        ];
    }
}