<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output as TaskOutput;
use App\Entity\Task\Task;
use App\Model\TaskTypePerformer\Response as TaskTypePerformerResponse;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\TaskOutputMessageFactory;
use GuzzleHttp\Psr7\Request;
use webignition\HtmlValidator\Output\Parser\Configuration as HtmlValidatorOutputParserConfiguration;
use webignition\HtmlValidator\Wrapper\Wrapper as HtmlValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentType\Extractor as DoctypeExtractor;
use webignition\HtmlDocumentType\Validator as DoctypeValidator;
use webignition\HtmlDocumentType\Factory as DoctypeFactory;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\WebResourceInterfaces\WebResourceInterface;

class HtmlValidationTaskTypePerformer implements TaskTypePerformerInterface
{
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';

    const CURL_CODE_INVALID_URL = 3;
    const CURL_CODE_TIMEOUT = 28;
    const CURL_CODE_DNS_LOOKUP_FAILURE = 6;

    /**
     * @var HttpException
     */
    private $httpException;

    /**
     * @var TransportException
     */
    private $transportException;

    /**
     * @var Task
     */
    protected $task;

    /**
     * @var WebResourceRetriever
     */
    private $webResourceRetriever;

    /**
     * @var HttpClientService
     */
    protected $httpClientService;

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
     * @var HtmlValidatorWrapper
     */
    private $htmlValidatorWrapper;

    /**
     * @var string
     */
    private $validatorPath;

    /**
     * @var TaskTypePerformerResponse
     */
    protected $response = null;

    public function __construct(
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        WebResourceRetriever $webResourceRetriever,
        HttpHistoryContainer $httpHistoryContainer,
        TaskOutputMessageFactory $taskOutputMessageFactory,
        HtmlValidatorWrapper $htmlValidatorWrapper,
        string $validatorPath
    ) {
        $this->httpClientService = $httpClientService;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->webResourceRetriever = $webResourceRetriever;
        $this->httpHistoryContainer = $httpHistoryContainer;
        $this->taskOutputMessageFactory = $taskOutputMessageFactory;

        $this->htmlValidatorWrapper = $htmlValidatorWrapper;
        $this->validatorPath = $validatorPath;
    }

    /**
     * @param Task $task
     *
     * @return TaskTypePerformerResponse
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    public function perform(Task $task): TaskTypePerformerResponse
    {
        $this->response = new TaskTypePerformerResponse();

        $rawOutput = $this->execute($task);

        $this->response->setTaskOutput(TaskOutput::create(
            $rawOutput,
            $this->getOutputContentType(),
            $this->response->getErrorCount(),
            $this->response->getWarningCount()
        ));

        return $this->response;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    private function execute(Task $task)
    {
        $this->task = $task;
        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        $webPage = $this->retrieveWebPage();

        if (!$this->response->hasSucceeded()) {
            return $this->hasNotSucceededHandler();
        }

        if (!$webPage instanceof WebPage) {
            $this->response->setHasBeenSkipped();
            $this->response->setIsRetryable(false);
            $this->response->setErrorCount(0);

            return null;
        }

        if (empty($webPage->getContent())) {
            $this->response->setHasBeenSkipped();
            $this->response->setErrorCount(0);

            return null;
        }

        return $this->performValidation($webPage);
    }

    /**
     * @return WebResourceInterface
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    private function retrieveWebPage()
    {
        $request = new Request('GET', $this->task->getUrl());

        try {
            return $this->webResourceRetriever->retrieve($request);
        } catch (InvalidResponseContentTypeException $invalidResponseContentTypeException) {
            $this->response->setHasBeenSkipped();
            $this->response->setIsRetryable(false);
            $this->response->setErrorCount(0);
        } catch (HttpException $httpException) {
            $this->httpException = $httpException;
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
        } catch (TransportException $transportException) {
            if (!$transportException->isCurlException() && !$transportException->isTooManyRedirectsException()) {
                throw $transportException;
            }

            $this->transportException = $transportException;
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    private function hasNotSucceededHandler()
    {
        $this->response->setErrorCount(1);

        return json_encode($this->taskOutputMessageFactory->createOutputMessageCollection(
            $this->httpException,
            $this->transportException
        ));
    }

    /**
     * {@inheritdoc}
     */
    private function performValidation(WebPage $webPage)
    {
        $webPageContent = $webPage->getContent();
        $docTypeString = DoctypeExtractor::extract($webPageContent);

        if (empty($docTypeString)) {
            $this->response->setErrorCount(1);
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);

            if ($this->isMarkup($webPageContent)) {
                return json_encode($this->getMissingDocumentTypeOutput());
            } else {
                return json_encode($this->getIsNotMarkupOutput($webPageContent));
            }
        }

        $doctypeValidator = new DoctypeValidator();
        $doctypeValidator->setMode(DoctypeValidator::MODE_IGNORE_FPI_URI_VALIDITY);

        try {
            $documentType = DoctypeFactory::createFromDocTypeString($docTypeString);

            if (!$doctypeValidator->isValid($documentType)) {
                return $this->createInvalidDocumentTypeResponse($docTypeString);
            }
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return $this->createInvalidDocumentTypeResponse($docTypeString);
        }

        $webPageCharacterSet = $webPage->getCharacterSet();

        if (empty($webPageCharacterSet)) {
            $webPageCharacterSet = self::DEFAULT_CHARACTER_ENCODING;
        }

        $this->htmlValidatorWrapper->configure([
            HtmlValidatorWrapper::CONFIG_KEY_DOCUMENT_URI => 'file:' . $this->storeTmpFile($webPageContent),
            HtmlValidatorWrapper::CONFIG_KEY_VALIDATOR_PATH => $this->validatorPath,
            HtmlValidatorWrapper::CONFIG_KEY_DOCUMENT_CHARACTER_SET => $webPageCharacterSet,
            HtmlValidatorWrapper::CONFIG_KEY_PARSER_CONFIGURATION_VALUES => [
                HtmlValidatorOutputParserConfiguration::KEY_CSS_VALIDATION_ISSUES => true,
            ],
        ]);

        $output = $this->htmlValidatorWrapper->validate();

        if ($output->wasAborted()) {
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
        }

        $outputObject = new \stdClass();
        $outputObject->messages = $output->getMessages();

        $this->response->setErrorCount((int)$output->getErrorCount());

        return json_encode($outputObject);
    }

    /**
     * @param string $docTypeString
     *
     * @return string
     */
    private function createInvalidDocumentTypeResponse($docTypeString)
    {
        $this->response->setErrorCount(1);
        $this->response->setHasFailed();
        $this->response->setIsRetryable(false);

        return json_encode($this->getInvalidDocumentTypeOutput($docTypeString));
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function storeTmpFile($content)
    {
        $filename = sys_get_temp_dir() . '/' . md5($content) . '.html';

        if (!file_exists($filename)) {
            file_put_contents($filename, $content);
        }

        return $filename;
    }

    /**
     * @param string $fragment
     *
     * @return boolean
     */
    private function isMarkup($fragment)
    {
        return strip_tags($fragment) !== $fragment;
    }

    /**
     * @return \stdClass
     */
    private function getMissingDocumentTypeOutput()
    {
        return (object)[
            'messages' => [
                [
                    'message' => 'No doctype',
                    'messageId' => 'document-type-missing',
                    'type' => 'error',
                ]
            ]
        ];
    }

    /**
     * @param $fragment
     *
     * @return \stdClass
     */
    private function getIsNotMarkupOutput($fragment)
    {
        return (object)[
            'messages' => [
                [
                    'message' => 'Not markup',
                    'messageId' => 'document-is-not-markup',
                    'type' => 'error',
                    'fragment' => $fragment,
                ]
            ],
        ];
    }

    /**
     * @param $documentType
     *
     * @return \stdClass
     */
    private function getInvalidDocumentTypeOutput($documentType)
    {
        return (object)[
            'messages' => [
                [
                    'message' => $documentType,
                    'messageId' => 'document-type-invalid',
                    'type' => 'error',
                ]
            ]
        ];
    }

    /**
     *
     * @return InternetMediaType
     */
    private function getOutputContentType()
    {
        $contentType = new InternetMediaType();
        $contentType->setType('application');
        $contentType->setSubtype('json');

        return $contentType;
    }
}
