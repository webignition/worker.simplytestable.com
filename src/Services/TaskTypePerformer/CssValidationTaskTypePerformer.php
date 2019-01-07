<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\TaskCachedSourceWebPageRetriever;
use webignition\CssValidatorOutput\Model\AbstractIssueMessage;
use webignition\CssValidatorOutput\Model\ExceptionOutput;
use webignition\CssValidatorOutput\Model\ValidationOutput as CssValidatorOutput;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebResource\WebPage\WebPage;

class CssValidationTaskTypePerformer implements TaskPerformerInterface
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

    /**
     * @var CssValidatorWrapperConfigurationFactory
     */
    private $configurationFactory;

    /**
     * @var int
     */
    private $priority;

    public function __construct(
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        CssValidatorWrapper $cssValidatorWrapper,
        CssValidatorWrapperConfigurationFactory $configurationFactory,
        int $priority
    ) {
        $this->httpClientService = $httpClientService;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;

        $this->cssValidatorWrapper = $cssValidatorWrapper;
        $this->configurationFactory = $configurationFactory;
        $this->priority = $priority;
    }

    /**
     * @param Task $task
     *
     * @return null
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidValidatorOutputException
     * @throws UnparseableContentTypeException
     */
    public function perform(Task $task)
    {
        if (!empty($task->getOutput())) {
            return null;
        }

        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        return $this->performValidation($task, $this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function handles(string $taskType): bool
    {
        return TypeInterface::TYPE_CSS_VALIDATION === $taskType;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param Task $task
     * @param WebPage $webPage
     *
     * @return null
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidValidatorOutputException
     * @throws UnparseableContentTypeException
     */
    private function performValidation(Task $task, WebPage $webPage)
    {
        $cssValidatorWrapperConfiguration = $this->configurationFactory->create(
            $task,
            (string) $webPage->getUri(),
            $webPage->getContent()
        );

        $this->cssValidatorWrapper->setHttpClient($this->httpClientService->getHttpClient());
        $cssValidatorOutput = $this->cssValidatorWrapper->validate($cssValidatorWrapperConfiguration);

        if ($cssValidatorOutput->isExceptionOutput()) {
            // Will only get unknown CSS validator exceptions here
            return $this->setTaskOutputAndState(
                $task,
                json_encode([
                    $this->getUnknownExceptionErrorOutput($task)
                ]),
                Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                1,
                0
            );
        }

        /* @var ValidationOutput $cssValidatorOutput */

        return $this->setTaskOutputAndState(
            $task,
            json_encode($this->prepareCssValidatorOutput($cssValidatorOutput)),
            Task::STATE_COMPLETED,
            $cssValidatorOutput->getMessages()->getErrorCount(),
            $cssValidatorOutput->getMessages()->getWarningCount()
        );
    }

    private function prepareCssValidatorOutput(CssValidatorOutput $cssValidatorOutput): array
    {
        $serializableMessages = [];
        $messageList = $cssValidatorOutput->getMessages();

        foreach ($messageList->getMessages() as $index => $message) {
            /* @var AbstractIssueMessage $message */
            if ($message->isError()) {
                if (ExceptionOutput::TYPE_HTTP === $message->getTitle()) {
                    $message = $message->withTitle('http-retrieval-' . $message->getContext());
                    $message = $message->withContext('');
                } elseif (ExceptionOutput::TYPE_CURL === $message->getTitle()) {
                    $message = $message->withTitle('http-retrieval-curl-code-' . $message->getContext());
                    $message = $message->withContext('');
                } elseif ('invalid-content-type' === $message->getTitle()) {
                    $message = $message->withTitle('invalid-content-type:' . $message->getContext());
                    $message = $message->withContext('');
                }
            }

            $serializableMessages[] = $message;
        }

        return $serializableMessages;
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
