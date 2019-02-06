<?php

namespace App\Services;

use App\Entity\Task\Task;
use webignition\CssValidatorOutput\Parser\Configuration;
use webignition\CssValidatorWrapper\VendorExtensionSeverityLevel as VExtLevel;

class CssValidatorOutputParserConfigurationFactory
{
    public function create(Task $task): Configuration
    {
        $taskParameters = $task->getParameters();

        $vendorExtensionSeverityLevel = $taskParameters->get('vendor-extensions');
        $vendorExtensionSeverityLevel = in_array($vendorExtensionSeverityLevel, VExtLevel::VALID_VALUES)
            ? $vendorExtensionSeverityLevel
            : VExtLevel::LEVEL_WARN;

        $ignoreWarnings = $taskParameters->get('ignore-warnings');
        $domainsToIgnore = $taskParameters->get('domains-to-ignore') ?? [];

        $ignoreVendorExtensionIssues = VExtLevel::LEVEL_IGNORE === $vendorExtensionSeverityLevel;
        $reportVendorExensionIssuesAsWarnings = VExtLevel::LEVEL_WARN === $vendorExtensionSeverityLevel;

        return new Configuration([
            Configuration::KEY_IGNORE_WARNINGS => $ignoreWarnings,
            Configuration::KEY_REF_DOMAINS_TO_IGNORE => $domainsToIgnore,
            Configuration::KEY_IGNORE_VENDOR_EXTENSION_ISSUES => $ignoreVendorExtensionIssues,
            Configuration::KEY_IGNORE_FALSE_DATA_URL_MESSAGES => true,
            Configuration::KEY_REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS => $reportVendorExensionIssuesAsWarnings,
        ]);
    }
}
