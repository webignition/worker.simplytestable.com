<?php

namespace App\Services\TaskDriver;

use QueryPath\Exception as QueryPathException;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\StateService;
use webignition\HtmlValidator\Output\Parser\Configuration as HtmlValidatorOutputParserConfiguration;
use webignition\HtmlValidator\Wrapper\Wrapper as HtmlValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentType\Extractor as DoctypeExtractor;
use webignition\HtmlDocumentType\Validator as DoctypeValidator;
use webignition\HtmlDocumentType\Factory as DoctypeFactory;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HtmlValidationTaskDriver extends AbstractWebPageTaskDriver
{
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';

    /**
     * @var HtmlValidatorWrapper
     */
    private $htmlValidatorWrapper;

    /**
     * @var string
     */
    private $validatorPath;

    /**
     * @param StateService $stateService
     * @param HttpClientService $httpClientService
     * @param HttpClientConfigurationService $httpClientConfigurationService
     * @param WebResourceRetriever $webResourceRetriever
     * @param HttpHistoryContainer $httpHistoryContainer
     * @param HtmlValidatorWrapper $htmlValidatorWrapper
     * @param $validatorPath
     */
    public function __construct(
        StateService $stateService,
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        WebResourceRetriever $webResourceRetriever,
        HttpHistoryContainer $httpHistoryContainer,
        HtmlValidatorWrapper $htmlValidatorWrapper,
        $validatorPath
    ) {
        parent::__construct(
            $stateService,
            $httpClientService,
            $httpClientConfigurationService,
            $webResourceRetriever,
            $httpHistoryContainer
        );

        $this->htmlValidatorWrapper = $htmlValidatorWrapper;
        $this->validatorPath = $validatorPath;
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
     * @throws QueryPathException
     */
    protected function performValidation(WebPage $webPage)
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
    protected function getMissingDocumentTypeOutput()
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
    protected function getIsNotMarkupOutput($fragment)
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
    protected function getInvalidDocumentTypeOutput($documentType)
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
    protected function getOutputContentType()
    {
        $contentType = new InternetMediaType();
        $contentType->setType('application');
        $contentType->setSubtype('json');

        return $contentType;
    }
}
