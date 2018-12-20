<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\TaskTypePerformer\Response as TaskTypePerformerResponse;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\TaskOutputMessageFactory;
use App\Services\TaskPerformerWebPageRetriever;
use HttpException;
use webignition\CssValidatorOutput\CssValidatorOutput;
use webignition\CssValidatorOutput\Message\AbstractMessage as CssValidatorOutputMessage;
use webignition\CssValidatorOutput\Message\AbstractMessage;
use webignition\CssValidatorOutput\Message\Error as CssValidatorOutputError;
use webignition\CssValidatorOutput\Message\Factory as CssValidatorOutputMessageFactory;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class CssValidationTaskTypePerformer implements TaskTypePerformerInterface
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';

    /**
     * @var HttpException
     */
    private $httpException;

    /**
     * @var TransportException
     */
    private $transportException;

    /**
     * @var WebResourceRetriever
     */
    private $webResourceRetriever;

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var HttpClientConfigurationService
     */
    private $httpClientConfigurationService;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * @var TaskOutputMessageFactory
     */
    private $taskOutputMessageFactory;

    /**
     * @var TaskPerformerWebPageRetriever
     */
    private $taskPerformerWebPageRetriever;

    /**
     * @var CssValidatorWrapper
     */
    private $cssValidatorWrapper;

    /**
     * @var CssValidatorWrapperConfigurationFactory
     */
    private $configurationFactory;

    public function __construct(
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        WebResourceRetriever $webResourceRetriever,
        HttpHistoryContainer $httpHistoryContainer,
        TaskOutputMessageFactory $taskOutputMessageFactory,
        TaskPerformerWebPageRetriever $taskPerformerWebPageRetriever,
        CssValidatorWrapper $cssValidatorWrapper,
        CssValidatorWrapperConfigurationFactory $configurationFactory
    ) {
        $this->httpClientService = $httpClientService;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->webResourceRetriever = $webResourceRetriever;
        $this->httpHistoryContainer = $httpHistoryContainer;
        $this->taskOutputMessageFactory = $taskOutputMessageFactory;
        $this->taskPerformerWebPageRetriever = $taskPerformerWebPageRetriever;

        $this->cssValidatorWrapper = $cssValidatorWrapper;
        $this->configurationFactory = $configurationFactory;
    }

    /**
     * @param Task $task
     *
     * @return TaskTypePerformerResponse
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    public function perform(Task $task): ?TaskTypePerformerResponse
    {
        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        $webPage = $this->taskPerformerWebPageRetriever->retrieveWebPage($task);

        if (!$task->isIncomplete()) {
            return null;
        }

        return $this->performValidation($task, $webPage);
    }

    /**
     * @return InternetMediaType
     */
    protected function getOutputContentType()
    {
        $contentType = new InternetMediaType();
        $contentType->setType('application');
        $contentType->setSubtype('json');

        return $contentType;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasNotSucceededHandler()
    {
        $this->response->setErrorCount(1);

        return json_encode($this->getHttpExceptionOutput());
    }

    /**
     * {@inheritdoc}
     */
    protected function isBlankWebResourceHandler()
    {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidValidatorOutputException
     * @throws InternetMediaTypeParseException
     * @throws UnparseableContentTypeException
     */
    protected function performValidation(Task $task, WebPage $webPage)
    {
        $cssValidatorWrapperConfiguration = $this->configurationFactory->create(
            $task,
            (string)$webPage->getUri(),
            $webPage->getContent()
        );

        $this->cssValidatorWrapper->setHttpClient($this->httpClientService->getHttpClient());
        $cssValidatorOutput = $this->cssValidatorWrapper->validate($cssValidatorWrapperConfiguration);

        if ($cssValidatorOutput->hasException()) {
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

        return $this->setTaskOutputAndState(
            $task,
            json_encode($this->prepareCssValidatorOutput($cssValidatorOutput)),
            Task::STATE_COMPLETED,
            $cssValidatorOutput->getErrorCount(),
            $cssValidatorOutput->getWarningCount()
        );

//        $this->response->setErrorCount($cssValidatorOutput->getErrorCount());
//        $this->response->setWarningCount($cssValidatorOutput->getWarningCount());
//
//        return json_encode($this->prepareCssValidatorOutput($cssValidatorOutput));
    }

    private function prepareCssValidatorOutput(CssValidatorOutput $cssValidatorOutput): array
    {
        $serializableMessages = [];
        $messages = $cssValidatorOutput->getMessages();

        foreach ($messages as $index => $message) {
            /* @var CssValidatorOutputMessage $message */

            if ($message->isError()) {
                /* @var $message CssValidatorOutputError */
                if ($this->isCssValidatorHttpError($message)) {
                    $modifiedMessageData = array_merge($message->jsonSerialize(), [
                        AbstractMessage::KEY_MESSAGE =>
                            'http-retrieval-' . $this->getCssValidatorHttpErrorStatusCode($message),
                    ]);

                    $message = CssValidatorOutputMessageFactory::createFromArray($modifiedMessageData);
                }

                if ($this->isCssValidatorCurlError($message)) {
                    $modifiedMessageData = array_merge($message->jsonSerialize(), [
                        AbstractMessage::KEY_MESSAGE =>
                            'http-retrieval-curl-code-' . $this->getCssValidatorCurlErrorCode($message)
                    ]);

                    $message = CssValidatorOutputMessageFactory::createFromArray($modifiedMessageData);
                }
            }

            $serializableMessages[] = $message->jsonSerialize();
        }

        return $serializableMessages;
    }

    /**
     * @param CssValidatorOutputError $error
     *
     * @return boolean
     */
    private function isCssValidatorHttpError(CssValidatorOutputError $error)
    {
        $message = $error->getMessage();

        return substr($message, 0, strlen('http-error:')) === 'http-error:';
    }

    /**
     * @param CssValidatorOutputError $error
     *
     * @return boolean
     */
    private function isCssValidatorCurlError(CssValidatorOutputError $error)
    {
        $message = $error->getMessage();

        return substr($message, 0, strlen('curl-error:')) === 'curl-error:';
    }

    /**
     * @param CssValidatorOutputError $error
     *
     * @return int
     */
    private function getCssValidatorHttpErrorStatusCode(CssValidatorOutputError $error)
    {
        return (int)str_replace('http-error:', '', $error->getMessage());
    }

    /**
     * @param CssValidatorOutputError $error
     *
     * @return int
     */
    private function getCssValidatorCurlErrorCode(CssValidatorOutputError $error)
    {
        return (int)str_replace('curl-error:', '', $error->getMessage());
    }

    /**
     * @param Task $task
     *
     * @return array
     */
    protected function getUnknownExceptionErrorOutput(Task $task)
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
            new InternetMediaType('application/json'),
            $errorCount,
            $warningCount
        ));

        $task->setState($state);

        return null;
    }
}
