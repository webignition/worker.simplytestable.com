<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use webignition\HtmlValidator\Wrapper\Wrapper as HtmlValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Service\Service as WebResourceService;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentType\Extractor as DoctypeExtractor;
use webignition\HtmlDocumentType\Validator as DoctypeValidator;

class HtmlValidationTaskDriver extends WebResourceTaskDriver
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
     * @param HttpClientService $httpClientService
     * @param WebResourceService $webResourceService
     * @param HtmlValidatorWrapper $htmlValidatorWrapper
     * @param StateService $stateService
     * @param string $validatorPath
     */
    public function __construct(
        HttpClientService $httpClientService,
        WebResourceService $webResourceService,
        HtmlValidatorWrapper $htmlValidatorWrapper,
        StateService $stateService,
        $validatorPath
    ) {
        $this->setHttpClientService($httpClientService);
        $this->setWebResourceService($webResourceService);
        $this->setHtmlValidatorWrapper($htmlValidatorWrapper);
        $this->setStateService($stateService);
        $this->validatorPath = $validatorPath;
    }

    /**
     * @param HtmlValidatorWrapper $wrapper
     */
    public function setHtmlValidatorWrapper(HtmlValidatorWrapper $wrapper)
    {
        $this->htmlValidatorWrapper = $wrapper;
    }

    /**
     * @inheritdoc
     */
    protected function hasNotSucceedHandler()
    {
        $this->response->setErrorCount(1);

        return json_encode($this->getWebResourceExceptionOutput());
    }

    /**
     * @inheritdoc
     */
    protected function isNotCorrectWebResourceTypeHandler()
    {
        $this->response->setHasBeenSkipped();
        $this->response->setIsRetryable(false);
        $this->response->setErrorCount(0);
    }

    /**
     * @inheritdoc
     */
    protected function isBlankWebResourceHandler()
    {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
    }

    protected function performValidation()
    {
        $doctypeExtractor = new DoctypeExtractor();
        $doctypeExtractor->setHtml($this->getWebPage()->getContent());

        if (!$doctypeExtractor->hasDocumentType()) {
            $this->response->setErrorCount(1);
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);

            if ($this->isMarkup($this->getWebPage()->getContent())) {
                return json_encode($this->getMissingDocumentTypeOutput());
            } else {
                return json_encode($this->getIsNotMarkupOutput($this->getWebPage()->getContent()));
            }
        }

        $doctypeValidator = new DoctypeValidator();
        if (!$doctypeValidator->isValid($doctypeExtractor->getDocumentTypeString())) {
            $this->response->setErrorCount(1);
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);

            return json_encode($this->getInvalidDocumentTypeOutput($doctypeExtractor->getDocumentTypeString()));
        }

        $this->htmlValidatorWrapper->createConfiguration(array(
            'documentUri' => 'file:' . $this->storeTmpFile($this->getWebPage()->getContent()),
            'validatorPath' => $this->validatorPath,
            'documentCharacterSet' => (is_null($this->getWebPage()->getCharacterSet()))
                ? self::DEFAULT_CHARACTER_ENCODING
                : $this->getWebPage()->getCharacterSet()
        ));

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
     * @return WebPage
     */
    private function getWebPage()
    {
        return $this->webResource;
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
     * @return boolean
     */
    protected function isCorrectWebResourceType()
    {
        return $this->webResource instanceof WebPage;
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
