<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use GuzzleHttp\Exception\ConnectException;
use SimplyTestable\WorkerBundle\Services\TaskDriver\LinkIntegrityTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

/**
 * Class LinkIntegrityTaskDriverTest
 * @package SimplyTestable\WorkerBundle\Tests\Services\TaskDriver
 *
 * @group foo-tests
 */
class LinkIntegrityTaskDriverTest extends FooWebResourceTaskDriverTest
{
    /**
     * @var LinkIntegrityTaskDriver
     */
    private $taskDriver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = $this->container->get('simplytestable.services.taskdriver.linkintegrity');
    }

    /**
     * @inheritdoc
     */
    protected function getTaskDriver()
    {
        return $this->taskDriver;
    }

    /**
     * @inheritdoc
     */
    protected function getTaskTypeString()
    {
        return strtolower(TaskTypeService::CSS_VALIDATION_NAME);
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param array $httpFixtures
     * @param array $taskParameters
     * @param bool $expectedHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param int $expectedWarningCount
     * @param array $expectedDecodedOutput
     */
    public function testPerform(
        $httpFixtures,
        $taskParameters,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedWarningCount,
        $expectedDecodedOutput
    ) {
        $this->setHttpFixtures($this->buildHttpFixtureSet($httpFixtures));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters),
        ]));

        $taskDriverResponse = $this->taskDriver->perform($task);

        $this->assertEquals($expectedHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedErrorCount, $taskDriverResponse->getErrorCount());
        $this->assertEquals($expectedWarningCount, $taskDriverResponse->getWarningCount());
        $this->assertEquals($expectedDecodedOutput, json_decode($taskDriverResponse->getTaskOutput()->getOutput()));
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        return [
            'no links' => [
                'httpFixtures' => [
                    $this->createHtmlDocumentHttpFixture(
                        200,
                        '<!doctype html><html><head></head><body></body></html>'
                    )
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'single 200 OK link' => [
                'httpFixtures' => [
                    $this->createHtmlDocumentHttpFixture(
                        200,
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                    "HTTP/1.1 200 OK",
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'context' => '<a href="/foo"></a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'single 404 Not Found link' => [
                'httpFixtures' => [
                    $this->createHtmlDocumentHttpFixture(
                        200,
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'context' => '<a href="/foo"></a>',
                        'state' => 404,
                        'type' => 'http',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'single curl 28 link' => [
                'httpFixtures' => [
                    $this->createHtmlDocumentHttpFixture(
                        200,
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                    'CURL/28 Operation timed out.',
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'context' => '<a href="/foo"></a>',
                        'state' => 28,
                        'type' => 'curl',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'excluded urls' => [
                'httpFixtures' => [
                    $this->createHtmlDocumentHttpFixture(
                        200,
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                ],
                'taskParameters' => [
                    LinkIntegrityTaskDriver::EXCLUDED_URLS_PARAMETER_NAME => [
                        'http://example.com/foo'
                    ],
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'excluded domains' => [
                'httpFixtures' => [
                    $this->createHtmlDocumentHttpFixture(
                        200,
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                ],
                'taskParameters' => [
                    LinkIntegrityTaskDriver::EXCLUDED_DOMAINS_PARAMETER_NAME => [
                        'example.com'
                    ],
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
        ];
    }

    /**
     * @param int $statusCode
     * @param string $htmlDocument
     * @return string
     */
    private function createHtmlDocumentHttpFixture($statusCode, $htmlDocument)
    {
        return sprintf("HTTP/1.0 %s\nContent-Type:text/html\n\n%s", $statusCode, $htmlDocument);
    }
}
