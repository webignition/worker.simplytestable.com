<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\LinkIntegrityResult;
use App\Model\Task\Type;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\HttpRetryMiddleware;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocument\LinkChecker\LinkChecker;

class LinkIntegritySingleUrlTaskTypePerformer
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';

    private $httpClientService;
    private $httpClientConfigurationService;
    private $linkCheckerConfigurationFactory;
    private $httpRetryMiddleware;

    public function __construct(
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory,
        HttpRetryMiddleware $httpRetryMiddleware
    ) {
        $this->httpClientService = $httpClientService;
        $this->httpClientConfigurationService = $httpClientConfigurationService;

        $this->linkCheckerConfigurationFactory = $linkCheckerConfigurationFactory;
        $this->httpRetryMiddleware = $httpRetryMiddleware;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        if (Type::TYPE_LINK_INTEGRITY_SINGLE_URL === (string) $taskEvent->getTask()->getType()) {
            $this->perform($taskEvent->getTask());
        }
    }

    public function perform(Task $task)
    {
        if (!empty($task->getOutput())) {
            return null;
        }

        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        return $this->performValidation($task);
    }

    private function performValidation(Task $task)
    {
        $linkChecker = new LinkChecker(
            $this->linkCheckerConfigurationFactory->create($task),
            $this->httpClientService->getHttpClient()
        );

        $this->httpRetryMiddleware->disable();

        $url = $task->getUrl();
        $element = $task->getParameters()->get('element');

        $outputContent = null;
        $outputContentType = new InternetMediaType('application', 'json');
        $outputErrorCount = 0;

        $linkState = $linkChecker->getLinkState($url);

        if ($linkState) {
            $linkInterityResult = new LinkIntegrityResult(
                $url,
                $element,
                $linkState
            );

            $outputContent = $linkInterityResult;
            $outputErrorCount = (int) $linkState->isError();
        }

        $task->setOutput(Output::create(
            (string) json_encode($outputContent),
            $outputContentType,
            $outputErrorCount
        ));

        $this->httpRetryMiddleware->enable();

        $task->setState(Task::STATE_COMPLETED);

        return null;
    }
}
