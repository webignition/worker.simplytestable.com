<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Services\Request\Factory\Task;

use App\Model\Task\TypeInterface;
use App\Request\Task\CreateRequest;
use App\Services\Request\Factory\Task\CreateRequestFactory;
use App\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createReturnsNullDataProvider
     */
    public function testCreateReturnsNull(Request $request)
    {
        $taskTypeService = new TaskTypeService([
            TypeInterface::TYPE_HTML_VALIDATION => [
                'selectable' => true,
            ],
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $createRequestFactory = new CreateRequestFactory($requestStack, $taskTypeService);
        $createRequest = $createRequestFactory->create();

        $this->assertNull($createRequest);
    }

    public function createReturnsNullDataProvider(): array
    {
        return [
            'empty task type' => [
                'request' => new Request(),
            ],
            'invalid task type' => [
                'request' => new Request([], [
                    'type' => 'invalid task type',
                ]),
            ],
            'non-selectable task type' => [
                'request' => new Request([], [
                    'type' => TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL,
                ]),
            ],
        ];
    }


    /**
     * @dataProvider createDataProvider
     */
    public function testCreateSuccess(
        Request $request,
        ?string $expectedTaskType,
        string $expectedUrl,
        string $expectedParameters,
        bool $expectedIsValid
    ) {
        $taskTypeService = new TaskTypeService([
            TypeInterface::TYPE_HTML_VALIDATION => [
                'selectable' => true,
            ],
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $createRequestFactory = new CreateRequestFactory($requestStack, $taskTypeService);
        $createRequest = $createRequestFactory->create();

        $this->assertInstanceOf(CreateRequest::class, $createRequest);

        if ($createRequest instanceof CreateRequest) {
            $this->assertEquals($expectedTaskType, $createRequest->getTaskType());
            $this->assertEquals($expectedUrl, $createRequest->getUrl());
            $this->assertEquals($expectedParameters, $createRequest->getParameters());
            $this->assertEquals($expectedIsValid, $createRequest->isValid());
        }
    }

    public function createDataProvider(): array
    {
        return [
            'no url, no parameters' => [
                'request' => new Request([], [
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                ]),
                'expectedTaskType' => TypeInterface::TYPE_HTML_VALIDATION,
                'expectedUrl' => '',
                'expectedParameters' => '',
                'expectedIsValid' => false,
            ],
            'has url, has parameters' => [
                'request' => new Request([], [
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                    'parameters' => 'parameters string value',
                ]),
                'expectedTaskType' => TypeInterface::TYPE_HTML_VALIDATION,
                'expectedUrl' => 'http://example.com/',
                'expectedParameters' => 'parameters string value',
                'expectedIsValid' => true,
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
