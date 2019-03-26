<?php

namespace App\Services;

use App\Entity\Task\Task;
use webignition\CssValidatorOutput\Parser\Flags;
use webignition\CssValidatorWrapper\VendorExtensionSeverityLevel as VExtLevel;

class CssValidatorOutputParserFlagsFactory
{
    public function create(Task $task): int
    {
        $flagsToApply = [];

        $taskParameters = $task->getParameters();

        $vendorExtensionSeverityLevel = $taskParameters->get('vendor-extensions');
        $vendorExtensionSeverityLevel = in_array($vendorExtensionSeverityLevel, VExtLevel::VALID_VALUES)
            ? $vendorExtensionSeverityLevel
            : VExtLevel::LEVEL_WARN;

        $ignoreWarnings = $taskParameters->get('ignore-warnings');
        $ignoreVendorExtensionIssues = VExtLevel::LEVEL_IGNORE === $vendorExtensionSeverityLevel;
        $reportVendorExensionIssuesAsWarnings = VExtLevel::LEVEL_WARN === $vendorExtensionSeverityLevel;


        if ($ignoreWarnings) {
            $flagsToApply[] = Flags::IGNORE_WARNINGS;
        }

        if ($ignoreVendorExtensionIssues) {
            $flagsToApply[] = Flags::IGNORE_VENDOR_EXTENSION_ISSUES;
        }

        if ($reportVendorExensionIssuesAsWarnings) {
            $flagsToApply[] = Flags::REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS;
        }

        $flagsToApply[] = Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES;

        $flags = null;

        foreach ($flagsToApply as $flag) {
            if (null === $flags) {
                $flags = $flag;
            } else {
                $flags = $flags | $flag;
            }
        }

        if (null === $flags) {
            $flags = Flags::NONE;
        }

        return $flags;
    }
}
