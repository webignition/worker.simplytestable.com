<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Task;
use webignition\HtmlDocument\LinkChecker\Configuration;
use webignition\UrlHealthChecker\Configuration as UrlHealthCheckerConfiguration;

class LinkCheckerConfigurationFactory
{
    const EXCLUDED_URLS_PARAMETER_NAME = 'excluded-urls';
    const EXCLUDED_DOMAINS_PARAMETER_NAME = 'excluded-domains';

    /**
     * @var string[]
     */
    private $userAgents;

    /**
     * @param array $userAgents
     */
    public function __construct(array $userAgents)
    {
        $this->userAgents = $userAgents;
    }

    /**
     * @param Task $task
     *
     * @return Configuration
     */
    public function create(Task $task)
    {
        $urlHealthCheckerConfiguration = new UrlHealthCheckerConfiguration([
            UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => [
                'GET',
            ],
            UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
            UrlHealthCheckerConfiguration::CONFIG_KEY_USER_AGENTS => $this->userAgents,
        ]);

        $configurationValues = [
            Configuration::KEY_URL_HEALTH_CHECKER_CONFIGURATION => $urlHealthCheckerConfiguration,
            Configuration::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON => true,
        ];

        $excludedUrls = $task->getParameters()->get(self::EXCLUDED_URLS_PARAMETER_NAME);
        if ($excludedUrls) {
            $configurationValues[Configuration::KEY_URLS_TO_EXCLUDE] = $excludedUrls;
        }

        $excludedDomains = $task->getParameters()->get(self::EXCLUDED_DOMAINS_PARAMETER_NAME);
        if ($excludedDomains) {
            $configurationValues[Configuration::KEY_DOMAINS_TO_EXCLUDE] = $excludedDomains;
        }

        return new Configuration($configurationValues);
    }
}
