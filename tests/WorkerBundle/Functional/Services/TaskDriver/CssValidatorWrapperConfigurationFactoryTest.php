<?php

namespace Tests\WorkerBundle\Functional\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\CssValidatorWrapperConfigurationFactory;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorWrapper\Configuration\Configuration as WrapperConfiguration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class CssValidatorWrapperConfigurationFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var CssValidatorWrapperConfigurationFactory
     */
    private $configurationFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configurationFactory = $this->container->get(CssValidatorWrapperConfigurationFactory::class);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array $taskParameters
     * @param string $urlToValidate
     * @param string $contentToValidate
     * @param string $expectedVendorExtensionSeverityLevel
     * @param bool $expectedOutputParserIgnoreWarnings
     * @param array $expectedOutputParserDomainsToIgnore
     * @param bool $expectedOutputParserIgnoreVendorExtensionIssues
     * @param bool $expectedOutputParserReportVendorExtensionIssuesAsWarnings
     */
    public function testCreate(
        array $taskParameters,
        $urlToValidate,
        $contentToValidate,
        $expectedVendorExtensionSeverityLevel,
        $expectedOutputParserIgnoreWarnings,
        array $expectedOutputParserDomainsToIgnore,
        $expectedOutputParserIgnoreVendorExtensionIssues,
        $expectedOutputParserReportVendorExtensionIssuesAsWarnings
    ) {
        $testTaskFactory = new TestTaskFactory($this->container);

        $task = $testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => TaskTypeService::CSS_VALIDATION_NAME,
                'parameters' => json_encode($taskParameters),
            ])
        );

        $configuration = $this->configurationFactory->create($task, $urlToValidate, $contentToValidate);

        $this->assertInstanceOf(WrapperConfiguration::class, $configuration);
        $this->assertEquals($expectedVendorExtensionSeverityLevel, $configuration->getVendorExtensionSeverityLevel());
        $this->assertEquals($urlToValidate, $configuration->getUrlToValidate());
        $this->assertEquals($contentToValidate, $configuration->getContentToValidate());

        $outputParserConfiguration = $configuration->getOutputParserConfiguration();

        $this->assertInstanceOf(OutputParserConfiguration::class, $outputParserConfiguration);
        $this->assertEquals($expectedOutputParserIgnoreWarnings, $outputParserConfiguration->getIgnoreWarnings());
        $this->assertEquals($expectedOutputParserDomainsToIgnore, $outputParserConfiguration->getRefDomainsToIgnore());
        $this->assertEquals(
            $expectedOutputParserIgnoreVendorExtensionIssues,
            $outputParserConfiguration->getIgnoreVendorExtensionIssues()
        );
        $this->assertTrue($outputParserConfiguration->getIgnoreFalseImageDataUrlMessages());
        $this->assertEquals(
            $expectedOutputParserReportVendorExtensionIssuesAsWarnings,
            $outputParserConfiguration->getReportVendorExtensionIssuesAsWarnings()
        );
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'no task parameters' => [
                'taskParameters' => [],
                'urlToValidate' => 'http://example.com/',
                'contentToValidate' => 'foo',
                'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedOutputParserIgnoreWarnings' => false,
                'expectedOutputParserDomainsToIgnore' => [],
                'expectedOutputParserIgnoreVendorExtensionIssues' => false,
                'expectedOutputParserReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'valid vendor extension severity level: warn' => [
                'taskParameters' => [],
                'urlToValidate' => 'http://example.com/',
                'contentToValidate' => 'foo',
                'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedOutputParserIgnoreWarnings' => false,
                'expectedOutputParserDomainsToIgnore' => [],
                'expectedOutputParserIgnoreVendorExtensionIssues' => false,
                'expectedOutputParserReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'valid vendor extension severity level: error' => [
                'taskParameters' => [
                    'vendor-extensions' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                ],
                'urlToValidate' => 'http://example.com/',
                'contentToValidate' => 'foo',
                'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                'expectedOutputParserIgnoreWarnings' => false,
                'expectedOutputParserDomainsToIgnore' => [],
                'expectedOutputParserIgnoreVendorExtensionIssues' => false,
                'expectedOutputParserReportVendorExtensionIssuesAsWarnings' => false,
            ],
            'vendor extension severity level: foo (invalid)' => [
                'taskParameters' => [
                    'vendor-extensions' => 'foo',
                ],
                'urlToValidate' => 'http://example.com/',
                'contentToValidate' => 'foo',
                'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedOutputParserIgnoreWarnings' => false,
                'expectedOutputParserDomainsToIgnore' => [],
                'expectedOutputParserIgnoreVendorExtensionIssues' => false,
                'expectedOutputParserReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'ignore warnings: false' => [
                'taskParameters' => [
                    'ignore-warnings' => false,
                ],
                'urlToValidate' => 'http://example.com/',
                'contentToValidate' => 'foo',
                'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedOutputParserIgnoreWarnings' => false,
                'expectedOutputParserDomainsToIgnore' => [],
                'expectedOutputParserIgnoreVendorExtensionIssues' => false,
                'expectedOutputParserReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'ignore warnings: true' => [
                'taskParameters' => [
                    'ignore-warnings' => true,
                ],
                'urlToValidate' => 'http://example.com/',
                'contentToValidate' => 'foo',
                'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedOutputParserIgnoreWarnings' => true,
                'expectedOutputParserDomainsToIgnore' => [],
                'expectedOutputParserIgnoreVendorExtensionIssues' => false,
                'expectedOutputParserReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'domains to ignore: empty' => [
                'taskParameters' => [
                    'domains-to-ignore' => [],
                ],
                'urlToValidate' => 'http://example.com/',
                'contentToValidate' => 'foo',
                'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedOutputParserIgnoreWarnings' => false,
                'expectedOutputParserDomainsToIgnore' => [],
                'expectedOutputParserIgnoreVendorExtensionIssues' => false,
                'expectedOutputParserReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'domains to ignore: non-empty' => [
                'taskParameters' => [
                    'domains-to-ignore' => ['foo', 'bar'],
                ],
                'urlToValidate' => 'http://example.com/',
                'contentToValidate' => 'foo',
                'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedOutputParserIgnoreWarnings' => false,
                'expectedOutputParserDomainsToIgnore' => ['foo', 'bar'],
                'expectedOutputParserIgnoreVendorExtensionIssues' => false,
                'expectedOutputParserReportVendorExtensionIssuesAsWarnings' => true,
            ],
        ];
    }
}
