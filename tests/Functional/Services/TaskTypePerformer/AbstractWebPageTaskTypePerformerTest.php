<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Task;
use App\Model\TaskPerformerWebPageRetrieverResult;
use App\Services\TaskPerformerWebPageRetriever;
use App\Services\TaskTypePerformer\TaskTypePerformerInterface;
use App\Tests\Services\ObjectPropertySetter;
use App\Tests\Services\TestTaskFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use GuzzleHttp\Psr7\Uri;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\WebResource\WebPage\WebPage;

abstract class AbstractWebPageTaskTypePerformerTest extends AbstractBaseTestCase
{
    /**
     * @var TestTaskFactory
     */
    protected $testTaskFactory;

    /**
     * @var HttpMockHandler
     */
    protected $httpMockHandler;

    /**
     * @var HttpHistoryContainer
     */
    protected $httpHistoryContainer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    abstract protected function getTaskTypePerformer(): TaskTypePerformerInterface;
    abstract protected function getTaskTypeString():string;

    /**
     * @return array
     */
    public function cookiesDataProvider()
    {
        return [
            'no cookies' => [
                'taskParameters' => [],
                'expectedRequestCookieHeader' => '',
            ],
            'single cookie' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                ],
                'expectedRequestCookieHeader' => 'foo=bar',
            ],
            'multiple cookies' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo1',
                            'Value' => 'bar1',
                            'Domain' => '.example.com',
                        ],
                        [
                            'Name' => 'foo2',
                            'Value' => 'bar2',
                            'Domain' => 'foo2.example.com',
                        ],
                        [
                            'Name' => 'foo3',
                            'Value' => 'bar3',
                            'Domain' => '.example.com',
                        ],
                    ],
                ],
                'expectedRequestCookieHeader' => 'foo1=bar1; foo3=bar3',
            ],
        ];
    }

    /**
     * @return array
     */
    public function httpAuthDataProvider()
    {
        return [
            'no auth' => [
                'taskParameters' => [],
                'expectedRequestAuthorizationHeaderValue' => '',
            ],
            'has auth' => [
                'taskParameters' => [
                    'http-auth-username' => 'foouser',
                    'http-auth-password' => 'foopassword',
                ],
                'expectedRequestAuthorizationHeaderValue' => 'foouser:foopassword',
            ],
        ];
    }

    protected function assertHttpAuthorizationSetOnAllRequests(
        int $expectedRequestCount,
        string $expectedRequestAuthorizationHeaderValue
    ) {
        /* @var array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount($expectedRequestCount, $historicalRequests);

        foreach ($historicalRequests as $historicalRequest) {
            $authorizationHeaderLine = $historicalRequest->getHeaderLine('authorization');

            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic ', '', $authorizationHeaderLine)
            );

            $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
        }
    }

    protected function assertCookieHeadeSetOnAllRequests(
        int $expectedRequestCount,
        string $expectedRequestCookieHeader
    ) {
        /* @var array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount($expectedRequestCount, $historicalRequests);

        foreach ($historicalRequests as $historicalRequest) {
            $cookieHeaderLine = $historicalRequest->getHeaderLine('cookie');
            $this->assertEquals($expectedRequestCookieHeader, $cookieHeaderLine);
        }
    }

    protected function setTaskPerformerWebPageRetrieverOnTaskPerformer(
        string $performerClass,
        Task $task,
        string $content
    ) {
        /** @noinspection PhpUnhandledExceptionInspection */
        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($content);
        $webPage = $webPage->setUri(new Uri($task->getUrl()));

        $taskPerformerWebPageRetrieverResult = new TaskPerformerWebPageRetrieverResult();
        $taskPerformerWebPageRetrieverResult->setWebPage($webPage);
        $taskPerformerWebPageRetrieverResult->setTaskState($task->getState());

        $taskPerformerWebPageRetriever = \Mockery::mock(TaskPerformerWebPageRetriever::class);
        $taskPerformerWebPageRetriever
            ->shouldReceive('retrieveWebPage')
            ->with($task)
            ->andReturn($taskPerformerWebPageRetrieverResult);

        ObjectPropertySetter::setProperty(
            $this->getTaskTypePerformer(),
            $performerClass,
            'taskPerformerWebPageRetriever',
            $taskPerformerWebPageRetriever
        );
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
    }
}
