<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Task;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\TaskOutputMessageFactory;
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
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class CssValidationTaskTypePerformer extends AbstractWebPageTaskTypePerformer
{
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
        CssValidatorWrapper $cssValidatorWrapper,
        CssValidatorWrapperConfigurationFactory $configurationFactory
    ) {
        parent::__construct(
            $httpClientService,
            $httpClientConfigurationService,
            $webResourceRetriever,
            $httpHistoryContainer,
            $taskOutputMessageFactory
        );

        $this->cssValidatorWrapper = $cssValidatorWrapper;
        $this->configurationFactory = $configurationFactory;
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
    protected function performValidation(WebPage $webPage)
    {
        $cssValidatorWrapperConfiguration = $this->configurationFactory->create(
            $this->task,
            (string)$webPage->getUri(),
            $webPage->getContent()
        );

        $this->cssValidatorWrapper->setHttpClient($this->httpClientService->getHttpClient());
        $cssValidatorOutput = $this->cssValidatorWrapper->validate($cssValidatorWrapperConfiguration);

        if ($cssValidatorOutput->hasException()) {
            // Will only get unknown CSS validator exceptions here
            $this->response->setHasFailed();
            $this->response->setErrorCount(1);
            $this->response->setIsRetryable(false);

            return json_encode([
                $this->getUnknownExceptionErrorOutput($this->task)
            ]);
        }

        $this->response->setErrorCount($cssValidatorOutput->getErrorCount());
        $this->response->setWarningCount($cssValidatorOutput->getWarningCount());

        return json_encode($this->prepareCssValidatorOutput($cssValidatorOutput));
    }

    /**
     * @param CssValidatorOutput $cssValidatorOutput
     *
     * @return array
     */
    private function prepareCssValidatorOutput(CssValidatorOutput $cssValidatorOutput)
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
}
