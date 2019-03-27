<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Task\Type;
use App\Services\CssValidatorErrorFactory;
use App\Services\CssValidatorOutputParserFlagsFactory;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\UrlSourceMapFactory;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\CssValidatorWrapper\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\WebPage\WebPage;

class CssValidationTaskTypePerformer
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';
    const HTTP_ERROR_TITLE_PREFIX = 'http-error:';
    const CURL_ERROR_TITLE_PREFIX = 'curl-error:';

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var HttpClientConfigurationService
     */
    private $httpClientConfigurationService;

    /**
     * @var TaskCachedSourceWebPageRetriever
     */
    private $taskCachedSourceWebPageRetriever;

    /**
     * @var CssValidatorWrapper
     */
    private $cssValidatorWrapper;

    private $urlSourceMapFactory;
    private $cssValidatorOutputParserFlagsFactory;
    private $cssValidatorErrorFactory;

    public function __construct(
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        CssValidatorWrapper $cssValidatorWrapper,
        UrlSourceMapFactory $urlSourceMapFactory,
        CssValidatorOutputParserFlagsFactory $cssValidatorOutputParserFlagsFactory,
        CssValidatorErrorFactory $cssValidatorErrorFactory
    ) {
        $this->httpClientService = $httpClientService;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;

        $this->cssValidatorWrapper = $cssValidatorWrapper;
        $this->urlSourceMapFactory = $urlSourceMapFactory;
        $this->cssValidatorOutputParserFlagsFactory = $cssValidatorOutputParserFlagsFactory;
        $this->cssValidatorErrorFactory = $cssValidatorErrorFactory;
    }

    /**
     * @param TaskEvent $taskEvent
     *
     * @throws InvalidValidatorOutputException
     * @throws UnknownSourceException
     */
    public function __invoke(TaskEvent $taskEvent)
    {
        if (Type::TYPE_CSS_VALIDATION === (string) $taskEvent->getTask()->getType()) {
            $this->perform($taskEvent->getTask());
        }
    }

    /**
     * @param Task $task
     *
     * @return null
     *
     * @throws InvalidValidatorOutputException
     * @throws UnknownSourceException
     */
    public function perform(Task $task)
    {
        if (!empty($task->getOutput())) {
            return null;
        }

        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        return $this->performValidation($task, $this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    /**
     * @param Task $task
     * @param WebPage $webPage
     *
     * @return null
     *
     * @throws InvalidValidatorOutputException
     * @throws UnknownSourceException
     */
    private function performValidation(Task $task, WebPage $webPage)
    {
        $taskParameters = $task->getParameters();

        $vendorExtensionSeverityLevel = $taskParameters->get('vendor-extensions');
        $vendorExtensionSeverityLevel = $vendorExtensionSeverityLevel ?? VendorExtensionSeverityLevel::LEVEL_WARN;

        $sourceMap = $this->urlSourceMapFactory->createForTask($task);
        $outputParserFlags = $this->cssValidatorOutputParserFlagsFactory->create($task);

        /** @noinspection PhpUnhandledExceptionInspection */
        $output = $this->cssValidatorWrapper->validate(
            $webPage,
            $sourceMap,
            $vendorExtensionSeverityLevel,
            $taskParameters->get('domains-to-ignore') ?? [],
            $outputParserFlags
        );

        if ($output instanceof ValidationOutput) {
            $messageList = $output->getMessages();

            $taskSources = $task->getSources();

            foreach ($taskSources as $taskSource) {
                if ($taskSource->isUnavailable() || $taskSource->isInvalid()) {
                    $errorMessage = $this->cssValidatorErrorFactory->createForUnavailableTaskSource($taskSource);
                    $messageList->addMessage($errorMessage);
                }
            }

            return $this->setTaskOutputAndState(
                $task,
                (string) json_encode(array_values($messageList->getMessages())),
                Task::STATE_COMPLETED,
                $messageList->getErrorCount(),
                $messageList->getWarningCount()
            );
        }

        return $this->setTaskOutputAndState(
            $task,
            (string) json_encode([
                $this->getUnknownExceptionErrorOutput($task)
            ]),
            Task::STATE_FAILED_NO_RETRY_AVAILABLE,
            1,
            0
        );
    }

    private function getUnknownExceptionErrorOutput(Task $task): array
    {
        return [
            'message' => 'Unknown error',
            'class' => 'css-validation-exception-unknown',
            'type' => 'error',
            'context' => '',
            'ref' => $task->getUrl(),
            'line_number' => 0,
        ];
    }

    private function setTaskOutputAndState(
        Task $task,
        string $output,
        string $state,
        int $errorCount,
        int $warningCount
    ) {
        $task->setOutput(Output::create(
            $output,
            new InternetMediaType('application', 'json'),
            $errorCount,
            $warningCount
        ));

        $task->setState($state);

        return null;
    }
}
