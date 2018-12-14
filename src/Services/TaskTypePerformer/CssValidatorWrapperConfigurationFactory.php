<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Task;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorWrapper\Configuration\Configuration as WrapperConfiguration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class CssValidatorWrapperConfigurationFactory
{
    /**
     * @var string
     */
    private $cssValidatorJarPath;

    /**
     * @param string $cssValidatorJarPath
     */
    public function __construct($cssValidatorJarPath)
    {
        $this->cssValidatorJarPath = $cssValidatorJarPath;
    }

    /**
     * @param Task $task
     * @param string $urlToValidate
     * @param string $contentToValidate
     *
     * @return WrapperConfiguration
     */
    public function create(Task $task, $urlToValidate, $contentToValidate)
    {
        $vendorExtensionsParameter = (string)$task->getParameters()->get('vendor-extensions');

        $vendorExtensionSeverityLevel = VendorExtensionSeverityLevel::isValid($vendorExtensionsParameter)
            ? $task->getParameters()->get('vendor-extensions')
            : VendorExtensionSeverityLevel::LEVEL_WARN;

        return new WrapperConfiguration([
            WrapperConfiguration::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH => $this->cssValidatorJarPath,
            WrapperConfiguration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL => $vendorExtensionSeverityLevel,
            WrapperConfiguration::CONFIG_KEY_URL_TO_VALIDATE => $urlToValidate,
            WrapperConfiguration::CONFIG_KEY_CONTENT_TO_VALIDATE => $contentToValidate,
            WrapperConfiguration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION =>
                $this->createOutputParserConfiguration($task, $vendorExtensionSeverityLevel),
        ]);
    }

    /**
     * @param Task $task
     * @param string $vendorExtensionSeverityLevel
     *
     * @return OutputParserConfiguration
     */
    private function createOutputParserConfiguration(Task $task, $vendorExtensionSeverityLevel)
    {
        $ignoreWarnings = $task->getParameters()->get('ignore-warnings');
        $domainsToIgnore = $task->getParameters()->get('domains-to-ignore') ?? [];

        $ignoreVendorExtensionIssues = VendorExtensionSeverityLevel::LEVEL_IGNORE === $vendorExtensionSeverityLevel;
        $reportVendorExensionIssuesAsWarnings =
            VendorExtensionSeverityLevel::LEVEL_WARN === $vendorExtensionSeverityLevel;

        $configurationValues = [
            OutputParserConfiguration::KEY_IGNORE_WARNINGS => $ignoreWarnings,
            OutputParserConfiguration::KEY_REF_DOMAINS_TO_IGNORE => $domainsToIgnore,
            OutputParserConfiguration::KEY_IGNORE_VENDOR_EXTENSION_ISSUES => $ignoreVendorExtensionIssues,
            OutputParserConfiguration::KEY_IGNORE_FALSE_DATA_URL_MESSAGES => true,
            OutputParserConfiguration::KEY_REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS =>
                $reportVendorExensionIssuesAsWarnings,
        ];

        return new OutputParserConfiguration($configurationValues);
    }
}
