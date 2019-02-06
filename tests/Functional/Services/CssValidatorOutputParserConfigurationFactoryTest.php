<?php

namespace App\Tests\Functional\Services;

use App\Model\Task\TypeInterface;
use App\Services\CssValidatorOutputParserConfigurationFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestTaskFactory;
use webignition\CssValidatorOutput\Parser\Configuration;
use webignition\CssValidatorWrapper\VendorExtensionSeverityLevel as VExtLevel;

class CssValidatorOutputParserConfigurationFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var CssValidatorOutputParserConfigurationFactory
     */
    private $configurationFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->configurationFactory = self::$container->get(CssValidatorOutputParserConfigurationFactory::class);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array $taskParameters
     * @param bool $expectedIgnoreWarnings
     * @param array $expectedDomainsToIgnore
     * @param bool $expectedIgnoreVendorExtensionIssues
     * @param bool $expectedReportVendorExtensionIssuesAsWarnings
     */
    public function testCreate(
        array $taskParameters,
        $expectedIgnoreWarnings,
        array $expectedDomainsToIgnore,
        $expectedIgnoreVendorExtensionIssues,
        $expectedReportVendorExtensionIssuesAsWarnings
    ) {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $task = $testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => TypeInterface::TYPE_CSS_VALIDATION,
                'parameters' => json_encode($taskParameters),
            ])
        );

        $configuration = $this->configurationFactory->create($task);

        $this->assertInstanceOf(Configuration::class, $configuration);

        $this->assertEquals($expectedIgnoreWarnings, $configuration->getIgnoreWarnings());
        $this->assertEquals($expectedDomainsToIgnore, $configuration->getRefDomainsToIgnore());
        $this->assertEquals($expectedIgnoreVendorExtensionIssues, $configuration->getIgnoreVendorExtensionIssues());
        $this->assertTrue($configuration->getIgnoreFalseImageDataUrlMessages());
        $this->assertEquals(
            $expectedReportVendorExtensionIssuesAsWarnings,
            $configuration->getReportVendorExtensionIssuesAsWarnings()
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
                'expectedIgnoreWarnings' => false,
                'expectedDomainsToIgnore' => [],
                'expectedIgnoreVendorExtensionIssues' => false,
                'expectedReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'valid vendor extension severity level: warn' => [
                'taskParameters' => [
                    'vendor-extensions' => VExtLevel::LEVEL_WARN,
                ],
                'expectedIgnoreWarnings' => false,
                'expectedDomainsToIgnore' => [],
                'expectedIgnoreVendorExtensionIssues' => false,
                'expectedReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'valid vendor extension severity level: error' => [
                'taskParameters' => [
                    'vendor-extensions' => VExtLevel::LEVEL_ERROR,
                ],
                'expectedIgnoreWarnings' => false,
                'expectedDomainsToIgnore' => [],
                'expectedIgnoreVendorExtensionIssues' => false,
                'expectedReportVendorExtensionIssuesAsWarnings' => false,
            ],
            'vendor extension severity level: foo (invalid)' => [
                'taskParameters' => [
                    'vendor-extensions' => 'foo',
                ],
                'expectedIgnoreWarnings' => false,
                'expectedDomainsToIgnore' => [],
                'expectedIgnoreVendorExtensionIssues' => false,
                'expectedReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'ignore warnings: false' => [
                'taskParameters' => [
                    'ignore-warnings' => false,
                ],
                'expectedIgnoreWarnings' => false,
                'expectedDomainsToIgnore' => [],
                'expectedIgnoreVendorExtensionIssues' => false,
                'expectedReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'ignore warnings: true' => [
                'taskParameters' => [
                    'ignore-warnings' => true,
                ],
                'expectedIgnoreWarnings' => true,
                'expectedDomainsToIgnore' => [],
                'expectedIgnoreVendorExtensionIssues' => false,
                'expectedReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'domains to ignore: empty' => [
                'taskParameters' => [
                    'domains-to-ignore' => [],
                ],
                'expectedIgnoreWarnings' => false,
                'expectedDomainsToIgnore' => [],
                'expectedIgnoreVendorExtensionIssues' => false,
                'expectedReportVendorExtensionIssuesAsWarnings' => true,
            ],
            'domains to ignore: non-empty' => [
                'taskParameters' => [
                    'domains-to-ignore' => ['foo', 'bar'],
                ],
                'expectedIgnoreWarnings' => false,
                'expectedDomainsToIgnore' => ['foo', 'bar'],
                'expectedIgnoreVendorExtensionIssues' => false,
                'expectedReportVendorExtensionIssuesAsWarnings' => true,
            ],
        ];
    }
}
