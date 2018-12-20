<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output as TaskOutput;
use App\Entity\Task\Task;
use App\Model\TaskTypePerformer\Response as TaskTypePerformerResponse;
use App\Services\HttpClientConfigurationService;
use App\Services\TaskOutputMessageFactory;
use App\Services\TaskPerformerWebPageRetriever;
use webignition\HtmlValidator\Output\Parser\Configuration as HtmlValidatorOutputParserConfiguration;
use webignition\HtmlValidator\Wrapper\Wrapper as HtmlValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentType\Extractor as DoctypeExtractor;
use webignition\HtmlDocumentType\Validator as DoctypeValidator;
use webignition\HtmlDocumentType\Factory as DoctypeFactory;

class HtmlValidationTaskTypePerformer implements TaskTypePerformerInterface
{
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';

    const CURL_CODE_INVALID_URL = 3;
    const CURL_CODE_TIMEOUT = 28;
    const CURL_CODE_DNS_LOOKUP_FAILURE = 6;

    /**
     * @var HttpClientConfigurationService
     */
    private $httpClientConfigurationService;

    /**
     * @var TaskPerformerWebPageRetriever
     */
    private $taskPerformerWebPageRetriever;

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
        HttpClientConfigurationService $httpClientConfigurationService,
        TaskPerformerWebPageRetriever $taskPerformerWebPageRetriever,
        HtmlValidatorWrapper $htmlValidatorWrapper,
        string $validatorPath
    ) {
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->taskPerformerWebPageRetriever = $taskPerformerWebPageRetriever;

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
    public function perform(Task $task): ?TaskTypePerformerResponse
    {
        $this->execute($task);

        return null;
    }

    /**
     * @param Task $task
     *
     * @return null
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    private function execute(Task $task)
    {
        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        $webPage = $this->taskPerformerWebPageRetriever->retrieveWebPage($task);

        if (!$task->isIncomplete()) {
            return null;
        }

        return $this->performValidation($task, $webPage);
    }

    /**
     * {@inheritdoc}
     */
    private function performValidation(Task $task, WebPage $webPage)
    {
        $webPageContent = $webPage->getContent();
        $docTypeString = DoctypeExtractor::extract($webPageContent);

        if (empty($docTypeString)) {
            $isMarkup = strip_tags($webPageContent) !== $webPageContent;
            $output = $isMarkup
                ? json_encode($this->getMissingDocumentTypeOutput())
                : json_encode($this->getIsNotMarkupOutput($webPageContent));

            return $this->setTaskOutputAndState($task, $output, Task::STATE_FAILED_NO_RETRY_AVAILABLE, 1);
        }

        $doctypeValidator = new DoctypeValidator();
        $doctypeValidator->setMode(DoctypeValidator::MODE_IGNORE_FPI_URI_VALIDITY);

        try {
            if (!$doctypeValidator->isValid(DoctypeFactory::createFromDocTypeString($docTypeString))) {
                return $this->setTaskOutputAndState(
                    $task,
                    json_encode($this->createInvalidDocumentTypeOutput($docTypeString)),
                    Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                    1
                );
            }
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return $this->setTaskOutputAndState(
                $task,
                json_encode($this->createInvalidDocumentTypeOutput($docTypeString)),
                Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                1
            );
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
        $state = $output->wasAborted() ? Task::STATE_FAILED_NO_RETRY_AVAILABLE : Task::STATE_COMPLETED;

        return $this->setTaskOutputAndState(
            $task,
            json_encode([
                'messages' => $output->getMessages(),
            ]),
            $state,
            (int) $output->getErrorCount()
        );
    }

    private function setTaskOutputAndState(Task $task, string $output, string $state, int $errorCount)
    {
        $task->setOutput(TaskOutput::create(
            $output,
            new InternetMediaType('application/json'),
            $errorCount
        ));

        $task->setState($state);

        return null;
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

    private function createInvalidDocumentTypeOutput(string $documentType): array
    {
        return [
            'messages' => [
                [
                    'message' => $documentType,
                    'messageId' => 'document-type-invalid',
                    'type' => 'error',
                ]
            ]
        ];
    }
}
