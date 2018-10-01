<?php

namespace App\Tests\Unit\Services\Request\Factory\Task;

use App\Model\Task\TypeInterface;
use App\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use App\Services\Request\Factory\Task\CreateRequestFactory;
use App\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateRequestCollectionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param Request $request
     * @param int $expectedCollectionCount
     */
    public function testCreate(Request $request, $expectedCollectionCount)
    {
        $taskTypeService = new TaskTypeService([
            TypeInterface::TYPE_HTML_VALIDATION => [
                'selectable' => true,
            ],
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $createRequestFactory = new CreateRequestFactory($requestStack, $taskTypeService);
        $createRequestCollectionFactory = new CreateRequestCollectionFactory($requestStack, $createRequestFactory);
        $createRequestCollection = $createRequestCollectionFactory->create();

        /* @var array $collection */
        $collection = $createRequestCollection->getCreateRequests();

        $this->assertCount($expectedCollectionCount, $collection);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'no requests' => [
                'request' => new Request(),
                'expectedCollectionCount' => 0,
            ],
            'request tasks not array' => [
                'request' => new Request([], [
                    'tasks' => '',
                ]),
                'expectedCollectionCount' => 0,
            ],
            'one valid task' => [
                'request' => new Request([], [
                    'tasks' => [
                        [
                            'type' => TypeInterface::TYPE_HTML_VALIDATION,
                            'url' => 'http://example.com/',
                        ],
                        [
                            'type' => 'bar',
                            'url' => 'http://example.com/',
                        ],
                    ],
                ]),
                'expectedCollectionCount' => 1,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
