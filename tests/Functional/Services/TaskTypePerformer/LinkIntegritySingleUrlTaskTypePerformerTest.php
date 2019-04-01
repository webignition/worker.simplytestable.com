<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskTypePerformer\LinkIntegritySingleUrlTaskTypePerformer;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Tests\Factory\ConnectExceptionFactory;
use webignition\InternetMediaType\InternetMediaType;

class LinkIntegritySingleUrlTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var LinkIntegritySingleUrlTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(LinkIntegritySingleUrlTaskTypePerformer::class);
    }

    public function testPerformAlreadyHasOutput()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL,
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
                json_decode((string) $output->getContent(), true)
            );
        }
    }

    public function performSuccessDataProvider(): array
    {
        return [
            '200 OK link' => [
                'httpFixtures' => [
                    new Response(),
                ],
                'taskParameters' => [
                    'element' => '<a href="/"></a>',
                ],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'context' => '<a href="/"></a>',
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/',
                ],
            ],
            '404 Not Found link' => [
                'httpFixtures' => [
                    new Response(404),
                    new Response(404),
                ],
                'taskParameters' => [
                    'element' => '<a href="/"></a>',
                ],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'context' => '<a href="/"></a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/',
                ],
            ],
            'curl 28 link' => [
                'httpFixtures' => [
                    ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                ],
                'taskParameters' => [
                    'element' => '<a href="/"></a>',
                ],
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'context' => '<a href="/"></a>',
                    'state' => 28,
                    'type' => 'curl',
                    'url' => 'http://example.com/',
                ],
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
            'type' => TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL,
            'parameters' => json_encode(array_merge($taskParameters, [
                'element' => '<a href="/"></a>',
            ])),
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
            new Response(200),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_LINK_INTEGRITY,
            'parameters' => json_encode(array_merge($taskParameters, [
                'element' => '<a href="/"></a>',
            ])),
        ]));

        $this->taskTypePerformer->perform($task);

        $this->assertHttpAuthorizationSetOnAllRequests(count($httpFixtures), $expectedRequestAuthorizationHeaderValue);
    }
}
