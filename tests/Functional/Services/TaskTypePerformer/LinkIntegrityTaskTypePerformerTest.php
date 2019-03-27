<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskTypePerformer\LinkCheckerConfigurationFactory;
use App\Services\TaskTypePerformer\LinkIntegrityTaskTypePerformer;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\HtmlDocumentFactory;
use webignition\InternetMediaType\InternetMediaType;

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

    public function testPerformAlreadyHasOutput()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_LINK_INTEGRITY,
        ]));

        $output = Output::create('', new InternetMediaType('application', 'json'));
        $task->setOutput($output);
        $this->assertSame($output, $task->getOutput());

        $taskState = $task->getState();

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($taskState, $task->getState());
        $this->assertSame($output, $task->getOutput());
    }

    /**
     * @dataProvider performSuccessDataProvider
     */
    public function testPerformSuccess(
        array $httpFixtures,
        array $taskParameters,
        string $webPageContent,
        string $expectedTaskState,
        int $expectedErrorCount,
        int $expectedWarningCount,
        array $expectedDecodedOutput
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_LINK_INTEGRITY,
            'parameters' => json_encode($taskParameters),
        ]));
        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask($task, $webPageContent);

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);

        if ($output instanceof Output) {
            $this->assertEquals('application/json', $output->getContentType());
            $this->assertEquals($expectedErrorCount, $output->getErrorCount());
            $this->assertEquals($expectedWarningCount, $output->getWarningCount());

            $this->assertEquals(
                $expectedDecodedOutput,
                json_decode((string) $output->getOutput(), true)
            );
        }
    }

    public function performSuccessDataProvider(): array
    {
        return [
            'no links' => [
                'httpFixtures' => [],
                'taskParameters' => [],
                'webPageContent' => '<!doctype html><html><head></head><body></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'single 200 OK link' => [
                'httpFixtures' => [
                    new Response(),
                ],
                'taskParameters' => [],
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
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
                    new Response(404),
                    new Response(404),
                ],
                'taskParameters' => [],
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
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
                    ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                ],
                'taskParameters' => [],
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
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
                'httpFixtures' => [],
                'taskParameters' => [
                    LinkCheckerConfigurationFactory::EXCLUDED_URLS_PARAMETER_NAME => [
                        'http://example.com/foo'
                    ],
                ],
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'excluded domains' => [
                'httpFixtures' => [],
                'taskParameters' => [
                    LinkCheckerConfigurationFactory::EXCLUDED_DOMAINS_PARAMETER_NAME => [
                        'example.com'
                    ],
                ],
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'ignored schemes, no sources' => [
                'httpFixtures' => [],
                'taskParameters' => [],
                'webPageContent' => HtmlDocumentFactory::load('ignored-link-integrity-schemes'),
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
            new Response(200),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_LINK_INTEGRITY,
            'parameters' => json_encode($taskParameters),
        ]));

        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask(
            $task,
            '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
        );

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
            new Response(200),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_LINK_INTEGRITY,
            'parameters' => json_encode($taskParameters),
        ]));

        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask(
            $task,
            '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
        );

        $this->taskTypePerformer->perform($task);

        $this->assertHttpAuthorizationSetOnAllRequests(count($httpFixtures), $expectedRequestAuthorizationHeaderValue);
    }
}
