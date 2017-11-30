<?php

namespace Tests\WorkerBundle\Functional\Services\TaskDriver;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\TaskDriver\JsLintTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Factory\HtmlDocumentFactory;
use Tests\WorkerBundle\Factory\JsLintFixtureFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use webignition\NodeJslint\Wrapper\Wrapper as NodeJslintWrapper;
use webignition\NodeJslintOutput\Exception as NodeJslintOutputException;
use webignition\NodeJslint\Wrapper\Configuration\Configuration as NodeJslintWrapperConfiguration;
use webignition\NodeJslint\Wrapper\Configuration\Flag\JsLint as JsLintFlag;
use webignition\NodeJslint\Wrapper\Configuration\Option\JsLint as JsLintOption;

class JsLintTaskDriverTest extends WebResourceTaskDriverTest
{
    /**
     * @var JsLintTaskDriver
     */
    private $taskDriver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = $this->container->get(JsLintTaskDriver::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskDriver()
    {
        return $this->taskDriver;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskTypeString()
    {
        return strtolower(TaskTypeService::JS_STATIC_ANALYSIS_NAME);
    }

    public function testIncorrectPathToNodeJsLint()
    {
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html",
            sprintf(
                "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-js-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:application/javascript",
            "HTTP/1.1 200 OK\nContent-type:application/javascript",
        ]);

        $task = $this->getTestTaskFactory()->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
            ])
        );

        JsLintFixtureFactory::set([
            JsLintFixtureFactory::load('incorrect-path-to-node-jslint.txt')
        ]);

        $this->expectException(NodeJslintOutputException::class);
        $this->expectExceptionMessage('node-jslint not found at "/home/example/node_modules/jslint/bin/jslint.js"');
        $this->expectExceptionCode(3);

        $this->taskDriver->perform($task);
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param array $httpFixtures
     * @param array $taskParameters
     * @param string[] $jsLintRawOutput
     * @param bool $expectedHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param int $expectedWarningCount
     * @param string[] $expectedDecodedOutputKeys
     */
    public function testPerform(
        $httpFixtures,
        $taskParameters,
        $jsLintRawOutput,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedWarningCount,
        $expectedDecodedOutputKeys
    ) {
        $this->setHttpFixtures($httpFixtures);

        $task = $this->getTestTaskFactory()->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        JsLintFixtureFactory::set($jsLintRawOutput);

        $taskDriverResponse = $this->taskDriver->perform($task);
        $decodedTaskOutput = json_decode($taskDriverResponse->getTaskOutput()->getOutput(), true);

        $this->assertEquals($expectedHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedErrorCount, $taskDriverResponse->getErrorCount());
        $this->assertEquals($expectedWarningCount, $taskDriverResponse->getWarningCount());
        $this->assertEquals($expectedDecodedOutputKeys, array_keys($decodedTaskOutput));
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        return [
            'no js' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('minimal')
                    ),
                ],
                'taskParameters' => [],
                'jsLintRawOutput' => [JsLintFixtureFactory::load('no-errors.json')],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'script elements, has errors' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 200 OK\nContent-type:application/javascript",
                    "HTTP/1.1 200 OK\nContent-type:application/javascript",
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                    'jslint-option-sloppy' => true,
                    'jslint-option-debug' => false,
                    'jslint-option-maxerr' => 50,
                    'jslint-option-predef' => 'window',
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('too-many-errors-stopped-at-seven-percent.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'redirect impacts script url' => [
                'httpFixtures' => [
                    "HTTP/1.1 301 Moved PermanentlyContent-type:text/html\nLocation: http://sub.example.com",
                    "HTTP/1.1 200 OK\nContent-type:text/html\nCache-Control: no-cache, no-store, must-revalidate",
                    "HTTP/1.1 301 Moved PermanentlyContent-type:text/html\nLocation: http://sub.example.com",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 200 OK\nContent-type:application/javascript",
                    "HTTP/1.1 200 OK\nContent-type:application/javascript",
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                    'jslint-option-sloppy' => true,
                    'jslint-option-debug' => false,
                    'jslint-option-maxerr' => 50,
                    'jslint-option-predef' => 'window',
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('too-many-errors-stopped-at-seven-percent.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://sub.example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'script elements, stopped' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 200 OK\nContent-type:application/javascript",
                    "HTTP/1.1 200 OK\nContent-type:application/javascript",
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                    'jslint-option-predef' => ['window'],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('stopped-no-error.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'invalid content type exception on linked resource' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    "HTTP/1.1 200 OK\nContent-type:text/html"
                ],
                'taskParameters' => [
                    'domains-to-ignore' => 'foo',
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 2,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    'http://bar.example.com/bar.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'http 404 getting linked resource' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'http 500 getting linked resource' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'curl 6 getting linked resource' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    ConnectExceptionFactory::create('CURL/6 foo'),
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'curl 28 getting linked resource' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    ConnectExceptionFactory::create('CURL/28 foo'),
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
        ];
    }

    /**
     * @dataProvider performsSetJslintConfigurationDataProvider
     *
     * @param array $taskParameters
     * @param array $expectedConfigurationValues
     */
    public function testPerformSetJslintConfiguration(
        $taskParameters,
        $expectedConfigurationValues
    ) {
        $content = 'foo';

        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html",
            "HTTP/1.1 200 OK\nContent-type:text/html\n\n" . $content,
        ]);

        $task = $this->getTestTaskFactory()->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        JsLintFixtureFactory::set([
            JsLintFixtureFactory::load('no-errors.json')
        ]);

        /* @var NodeJslintWrapper|MockInterface $nodeJslintWrapper */
        $nodeJslintWrapper = \Mockery::spy(
            $this->container->get('simplytestable.services.nodejslintwrapper')
        );

        $this->getTaskDriver()->setNodeJsLintWrapper($nodeJslintWrapper);

        $this->taskDriver->perform($task);

        $standardConfigurationValues = [
            NodeJslintWrapperConfiguration::CONFIG_KEY_NODE_JSLINT_PATH =>
                $this->container->getParameter('node-jslint-path'),
            NodeJslintWrapperConfiguration::CONFIG_KEY_NODE_PATH =>
                $this->container->getParameter('node-path'),
        ];

        $nodeJslintWrapper
            ->shouldHaveReceived('createConfiguration')
            ->once()
            ->with(array_merge($expectedConfigurationValues, $standardConfigurationValues));
    }

    /**
     * @return array
     */
    public function performsSetJslintConfigurationDataProvider()
    {
        $allFlags = JsLintFlag::getList();
        $allConfigurationFlagsEnabled = [];
        $allConfigurationFlagsDisabled = [];
        $allParameterFlagsEnabled = [];
        $allParameterFlagsDisabled = [];

        $allConfigurationOptionsSet = [
            JSLintOPtion::INDENT => 12,
            JSLintOPtion::MAXERR => 99,
            JSLintOPtion::MAXLEN => 15,
            JSLintOPtion::PREDEF => ['window'],
        ];

        $allParameterOptionsSet = [
            'jslint-option-' . JSLintOPtion::INDENT => 12,
            'jslint-option-' . JSLintOPtion::MAXERR => 99,
            'jslint-option-' . JSLintOPtion::MAXLEN => 15,
            'jslint-option-' . JSLintOPtion::PREDEF => 'window',
        ];

        foreach ($allFlags as $name) {
            $allConfigurationFlagsEnabled[$name] = true;
            $allConfigurationFlagsDisabled[$name] = false;
            $allParameterFlagsEnabled['jslint-option-' . $name] = true;
            $allParameterFlagsDisabled['jslint-option-' . $name] = false;
        }

        return [
            'default' => [
                'taskParameters' => [],
                'expectedConfigurationValues' => [
                    NodeJslintWrapperConfiguration::CONFIG_KEY_FLAGS => [],
                    NodeJslintWrapperConfiguration::CONFIG_KEY_OPTIONS => [],
                ],
            ],
            'enable all boolean parameters' => [
                'taskParameters' => array_merge($allParameterFlagsEnabled, $allParameterOptionsSet),
                'expectedConfigurationValues' => [
                    NodeJslintWrapperConfiguration::CONFIG_KEY_FLAGS => $allConfigurationFlagsEnabled,
                    NodeJslintWrapperConfiguration::CONFIG_KEY_OPTIONS => $allConfigurationOptionsSet,
                ],
            ],
        ];
    }

    /**
     * @dataProvider cookiesDataProvider
     *
     * {@inheritdoc}
     */
    public function testSetCookiesOnHttpClient($taskParameters, $expectedRequestCookieHeader)
    {
        $this->setHttpFixtures([
            sprintf(
                "HTTP/1.1 200\nContent-Type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-js-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:application/javascript\n\n"
        ]);

        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        JsLintFixtureFactory::set([
            JsLintFixtureFactory::load('no-errors.json')
        ]);

        $this->taskDriver->perform($task);

        foreach ($this->container->get(HttpClientService::class)->getHistory()->getRequests(true) as $request) {
            $this->assertEquals($expectedRequestCookieHeader, $request->getHeader('cookie'));
        }
    }

    /**
     * @dataProvider httpAuthDataProvider
     *
     * {@inheritdoc}
     */
    public function testSetHttpAuthOnHttpClient($taskParameters, $expectedRequestAuthorizationHeaderValue)
    {
        $this->setHttpFixtures([
            sprintf(
                "HTTP/1.1 200\nContent-Type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-js-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:application/javascript\n\n"
        ]);

        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        JsLintFixtureFactory::set([
            JsLintFixtureFactory::load('no-errors.json')
        ]);

        $this->taskDriver->perform($task);

        foreach ($this->container->get(HttpClientService::class)->getHistory()->getRequests(true) as $request) {
            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic', '', $request->getHeader('authorization'))
            );

            $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
        }
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
