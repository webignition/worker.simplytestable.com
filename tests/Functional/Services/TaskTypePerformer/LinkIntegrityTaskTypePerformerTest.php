<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskTypePerformer\TaskTypePerformerInterface;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskTypePerformer\LinkCheckerConfigurationFactory;
use App\Services\TaskTypePerformer\LinkIntegrityTaskTypePerformer;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\HtmlDocumentFactory;

class LinkIntegrityTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var LinkIntegrityTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(LinkIntegrityTaskTypePerformer::class);
    }

    protected function getTaskTypePerformer(): TaskTypePerformerInterface
    {
        return $this->taskTypePerformer;
    }

    protected function getTaskTypeString(): string
    {
        return TypeInterface::TYPE_LINK_INTEGRITY;
    }

    /**
     * @dataProvider performSuccessDataProvider
     */
    public function testPerformSuccess(
        array $httpFixtures,
        array $taskParameters,
        string $expectedTaskState,
        int $expectedErrorCount,
        int $expectedWarningCount,
        array $expectedDecodedOutput
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters),
        ]));

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals($expectedErrorCount, $output->getErrorCount());
        $this->assertEquals($expectedWarningCount, $output->getWarningCount());

        $this->assertEquals(
            $expectedDecodedOutput,
            json_decode($output->getOutput(), true)
        );
    }

    public function performSuccessDataProvider(): array
    {
        $notFoundResponse = new Response(404);
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out.');

        return [
            'no links' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body></body></html>'
                    ),
                ],
                'taskParameters' => [],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'single 200 OK link' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                    new Response(),
                ],
                'taskParameters' => [],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'context' => '<a href="/foo"></a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'single 404 Not Found link' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'taskParameters' => [],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'context' => '<a href="/foo"></a>',
                        'state' => 404,
                        'type' => 'http',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'single curl 28 link' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                    $curl28ConnectException,
                ],
                'taskParameters' => [],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'context' => '<a href="/foo"></a>',
                        'state' => 28,
                        'type' => 'curl',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'excluded urls' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                ],
                'taskParameters' => [
                    LinkCheckerConfigurationFactory::EXCLUDED_URLS_PARAMETER_NAME => [
                        'http://example.com/foo'
                    ],
                ],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'excluded domains' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                ],
                'taskParameters' => [
                    LinkCheckerConfigurationFactory::EXCLUDED_DOMAINS_PARAMETER_NAME => [
                        'example.com'
                    ],
                ],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'ignored schemes' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('ignored-link-integrity-schemes')
                    ),
                ],
                'taskParameters' => [],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
        ];
    }

    /**
     * @dataProvider cookiesDataProvider
     */
    public function testSetCookiesOnRequests(array $taskParameters, string $expectedRequestCookieHeader)
    {
        $httpFixtures = [
            new Response(200, ['content-type' => 'text/html']),
            new Response(
                200,
                ['content-type' => 'text/html'],
                '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
            ),
            new Response(200),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->taskTypePerformer->perform($task);

        $this->assertCookieHeadeSetOnAllRequests(count($httpFixtures), $expectedRequestCookieHeader);
    }

    /**
     * @dataProvider httpAuthDataProvider
     */
    public function testSetHttpAuthenticationOnRequests(
        array $taskParameters,
        string $expectedRequestAuthorizationHeaderValue
    ) {
        $httpFixtures = [
            new Response(200, ['content-type' => 'text/html']),
            new Response(
                200,
                ['content-type' => 'text/html'],
                '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
            ),
            new Response(200),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->taskTypePerformer->perform($task);

        $this->assertHttpAuthorizationSetOnAllRequests(count($httpFixtures), $expectedRequestAuthorizationHeaderValue);
    }
}
