<?php

namespace App\Tests\Unit\Services\Request\Factory\Task;

use App\Model\Task\TypeInterface;
use App\Services\Request\Factory\Task\CreateRequestFactory;
use App\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param Request $request
     * @param string|null $expectedTaskType
     * @param string $expectedUrl
     * @param string $expectedParameters
     */
    public function testCreate(
        Request $request,
        ?string $expectedTaskType,
        string $expectedUrl,
        string $expectedParameters
    ) {
        $taskTypeService = new TaskTypeService([
            TypeInterface::TYPE_HTML_VALIDATION => [],
        ]);


        $requestStack = new RequestStack();
        $requestStack->push($request);

        $createRequestFactory = new CreateRequestFactory($requestStack, $taskTypeService);
        $createRequest = $createRequestFactory->create();

        $this->assertEquals($expectedTaskType, $createRequest->getTaskType());
        $this->assertEquals($expectedUrl, $createRequest->getUrl());
        $this->assertEquals($expectedParameters, $createRequest->getParameters());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'empty task type' => [
                'request' => new Request(),
                'expectedTaskType' => null,
                'expectedUrl' => '',
                'expectedParameters' => '',
            ],
            'invalid task type' => [
                'request' => new Request([], [
                    'type' => 'invalid task type',
                ]),
                'expectedTaskType' => null,
                'expectedUrl' => '',
                'expectedParameters' => '',
            ],
            'valid task type' => [
                'request' => new Request([], [
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                ]),
                'expectedTaskType' => TypeInterface::TYPE_HTML_VALIDATION,
                'expectedUrl' => '',
                'expectedParameters' => '',
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
