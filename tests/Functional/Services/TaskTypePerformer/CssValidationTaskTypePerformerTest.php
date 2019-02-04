<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskTypePerformer\CssValidationTaskTypePerformer;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\CssValidatorFixtureFactory;
use App\Tests\Factory\HtmlDocumentFactory;

class CssValidationTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var CssValidationTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(CssValidationTaskTypePerformer::class);
    }

    public function testPerformAlreadyHasOutput()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_CSS_VALIDATION,
        ]));

        $output = Output::create();
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
        string $cssValidatorOutput,
        string $expectedTaskState,
        int $expectedErrorCount,
        int $expectedWarningCount,
        array $expectedDecodedOutput
    ) {
        $this->markTestSkipped('Fix in  #389');

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_CSS_VALIDATION,
            'parameters' => json_encode($taskParameters),
        ]));
        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask($task, $webPageContent);

        CssValidatorFixtureFactory::set($cssValidatorOutput);

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals('application/json', $output->getContentType());
        $this->assertEquals($expectedErrorCount, $output->getErrorCount());
        $this->assertEquals($expectedWarningCount, $output->getWarningCount());

        $this->assertEquals(
            $expectedDecodedOutput,
            json_decode($output->getOutput(), true)
        );
    }

    public function performSuccessDataProvider(): array
    {
        return [
            'unknown validator exception' => [
                'httpFixtures' => [],
                'taskParameters' => [],
                'webPageContent' => 'foo',
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('unknown-exception'),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'message' => 'Unknown error',
                        'class' => 'css-validation-exception-unknown',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/',
                        'line_number' => 0,
                    ],
                ],
            ],
// @TODO: Fix in #389
//            'no errors, ignore warnings' => [
//                'httpFixtures' => [],
//                'taskParameters' => [
//                    'ignore-warnings' => true,
//                ],
//                'webPageContent' => 'foo',
//                'cssValidatorOutput' => CssValidatorFixtureFactory::load('1-vendor-extension-warning'),
//                'expectedTaskState' => Task::STATE_COMPLETED,
//                'expectedErrorCount' => 0,
//                'expectedWarningCount' => 0,
//                'expectedDecodedOutput' => [],
//            ],
//            'no errors, ignore vendor extension warnings' => [
//                'httpFixtures' => [],
//                'taskParameters' => [
//                    'vendor-extensions' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
//                ],
//                'webPageContent' => 'foo',
//                'cssValidatorOutput' => CssValidatorFixtureFactory::load('1-vendor-extension-warning'),
//                'expectedTaskState' => Task::STATE_COMPLETED,
//                'expectedErrorCount' => 0,
//                'expectedWarningCount' => 0,
//                'expectedDecodedOutput' => [],
//            ],
//            'three errors' => [
//                'httpFixtures' => [],
//                'taskParameters' => [
//                    'ignore-warnings' => true,
//                ],
//                'webPageContent' => 'foo',
//                'cssValidatorOutput' => CssValidatorFixtureFactory::load('3-errors'),
//                'expectedTaskState' => Task::STATE_COMPLETED,
//                'expectedErrorCount' => 3,
//                'expectedWarningCount' => 0,
//                'expectedDecodedOutput' => [
//                    [
//                        'message' => 'one',
//                        'context' => 'audio, canvas, video',
//                        'line_number' => 1,
//                        'type' => 'error',
//                        'ref' => 'http://example.com/',
//                    ],
//                    [
//                        'message' => 'two',
//                        'context' => 'html',
//                        'line_number' => 2,
//                        'type' => 'error',
//                        'ref' => 'http://example.com/',
//                    ],
//                    [
//                        'message' => 'three',
//                        'context' => '.hide-text',
//                        'line_number' => 3,
//                        'type' => 'error',
//                        'ref' => 'http://example.com/',
//                    ],
//                ],
//            ],
//            'http 404 getting linked resource' => [
//                'httpFixtures' => [
//                    new Response(404),
//                    new Response(404),
//                ],
//                'taskParameters' => [],
//                'webPageContent' => HtmlDocumentFactory::load('empty-body-single-css-link'),
//                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages'),
//                'expectedTaskState' => Task::STATE_COMPLETED,
//                'expectedErrorCount' => 1,
//                'expectedWarningCount' => 0,
//                'expectedDecodedOutput' => [
//                    [
//                        'message' => 'http-retrieval-404',
//                        'type' => 'error',
//                        'context' => '',
//                        'ref' => 'http://example.com/style.css',
//                        'line_number' => 0,
//                    ],
//                ],
//            ],
//            'http 500 getting linked resource' => [
//                'httpFixtures' => array_fill(0, 12, new Response(500)),
//                'taskParameters' => [],
//                'webPageContent' => HtmlDocumentFactory::load('empty-body-single-css-link'),
//                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages'),
//                'expectedTaskState' => Task::STATE_COMPLETED,
//                'expectedErrorCount' => 1,
//                'expectedWarningCount' => 0,
//                'expectedDecodedOutput' => [
//                    [
//                        'message' => 'http-retrieval-500',
//                        'type' => 'error',
//                        'context' => '',
//                        'ref' => 'http://example.com/style.css',
//                        'line_number' => 0,
//                    ],
//                ],
//            ],
//            'curl 6 getting linked resource' => [
//                'httpFixtures' => array_fill(0, 12, ConnectExceptionFactory::create('CURL/6 foo')),
//                'taskParameters' => [],
//                'webPageContent' => HtmlDocumentFactory::load('empty-body-single-css-link'),
//                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages'),
//                'expectedTaskState' => Task::STATE_COMPLETED,
//                'expectedErrorCount' => 1,
//                'expectedWarningCount' => 0,
//                'expectedDecodedOutput' => [
//                    [
//                        'message' => 'http-retrieval-curl-code-6',
//                        'type' => 'error',
//                        'context' => '',
//                        'ref' => 'http://example.com/style.css',
//                        'line_number' => 0,
//                    ],
//                ],
//            ],
//            'invalid content type getting linked resource' => [
//                'httpFixtures' => [
//                    new Response(200, ['content-type' => 'application/pdf']),
//                ],
//                'taskParameters' => [],
//                'webPageContent' => HtmlDocumentFactory::load('empty-body-single-css-link'),
//                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages'),
//                'expectedTaskState' => Task::STATE_COMPLETED,
//                'expectedErrorCount' => 1,
//                'expectedWarningCount' => 0,
//                'expectedDecodedOutput' => [
//                    [
//                        'message' => 'invalid-content-type:application/pdf',
//                        'type' => 'error',
//                        'context' => '',
//                        'ref' => 'http://example.com/style.css',
//                        'line_number' => 0,
//                    ],
//                ],
//            ],
        ];
    }

    /**
     * @dataProvider cookiesDataProvider
     */
    public function testSetCookiesOnRequests(array $taskParameters, string $expectedRequestCookieHeader)
    {
        $this->markTestSkipped('Fix in #389');

        $httpFixtures = [
            new Response(200, ['content-type' => 'text/css']),
            new Response(200, ['content-type' => 'text/css']),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_CSS_VALIDATION,
            'parameters' => json_encode($taskParameters),
        ]));

        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask(
            $task,
            HtmlDocumentFactory::load('empty-body-single-css-link')
        );

        CssValidatorFixtureFactory::set(CssValidatorFixtureFactory::load('no-messages'));

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
        $this->markTestSkipped('Fix in #389');

        $httpFixtures = [
            new Response(200, ['content-type' => 'text/css']),
            new Response(200, ['content-type' => 'text/css']),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_CSS_VALIDATION,
            'parameters' => json_encode($taskParameters),
        ]));

        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask(
            $task,
            HtmlDocumentFactory::load('empty-body-single-css-link')
        );

        CssValidatorFixtureFactory::set(CssValidatorFixtureFactory::load('no-messages'));

        $this->taskTypePerformer->perform($task);

        $this->assertHttpAuthorizationSetOnAllRequests(count($httpFixtures), $expectedRequestAuthorizationHeaderValue);
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
