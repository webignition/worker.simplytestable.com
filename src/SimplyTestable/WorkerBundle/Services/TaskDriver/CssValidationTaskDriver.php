<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use JMS\Serializer\Serializer;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\CssValidatorOutput\CssValidatorOutput;
use webignition\CssValidatorOutput\Message\Message as CssValidatorOutputMessage;
use webignition\CssValidatorOutput\Message\Error as CssValidatorOutputError;
use webignition\CssValidatorWrapper\Configuration\Configuration as CssValidatorWrapperConfiguration;
use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Service\Service as WebResourceService;
use webignition\WebResource\WebPage\WebPage;
use webignition\CssValidatorWrapper\Configuration\Flags as CssValidatorWrapperConfigurationFlags;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class CssValidationTaskDriver extends WebResourceTaskDriver
{
    /**
     * @var CssValidatorWrapper
     */
    private $cssValidatorWrapper;

    /**
     * @var string
     */
    private $cssValidatorJarPath;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param HttpClientService $httpClientService
     * @param WebResourceService $webResourceService
     * @param CssValidatorWrapper $cssValidatorWrapper
     * @param Serializer $serializer
     * @param StateService $stateService
     * @param string $cssValidatorJarPath
     */
    public function __construct(
        HttpClientService $httpClientService,
        WebResourceService $webResourceService,
        CssValidatorWrapper $cssValidatorWrapper,
        Serializer $serializer,
        StateService $stateService,
        $cssValidatorJarPath
    ) {
        $this->setHttpClientService($httpClientService);
        $this->setWebResourceService($webResourceService);
        $this->setCssValidatorWrapper($cssValidatorWrapper);
        $this->serializer = $serializer;
        $this->setStateService($stateService);
        $this->cssValidatorJarPath = $cssValidatorJarPath;
    }

    /**
     * @param CssValidatorWrapper $wrapper
     */
    public function setCssValidatorWrapper(CssValidatorWrapper $wrapper)
    {
        $this->cssValidatorWrapper = $wrapper;
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

    /**
     * @return string
     */
    protected function hasNotSucceedHandler()
    {
        $this->response->setErrorCount(1);

        return json_encode($this->getWebResourceExceptionOutput());
    }

    /**
     * @inheritdoc
     */
    protected function isBlankWebResourceHandler()
    {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
    }

    /**
     * @return boolean
     */
    protected function isCorrectWebResourceType()
    {
        return $this->webResource instanceof WebPage;
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
    protected function performValidation()
    {
        $vendorExtensionsParameter = $this->task->getParameter('vendor-extensions');

        $vendorExtensionSeverityLevel = VendorExtensionSeverityLevel::isValid($vendorExtensionsParameter)
            ? $this->task->getParameter('vendor-extensions')
            : VendorExtensionSeverityLevel::LEVEL_WARN;

        $cssValidatorFlags = [
            CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES
        ];

        $domainsToIgnore = $this->task->hasParameter('domains-to-ignore')
            ? $this->task->getParameter('domains-to-ignore')
            : [];

        if ($this->task->getParameter('ignore-warnings')) {
            $cssValidatorFlags[] = CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_WARNINGS;
        }

        $configurationValues = [
            CssValidatorWrapperConfiguration::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH =>
                $this->cssValidatorJarPath,
            CssValidatorWrapperConfiguration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                $vendorExtensionSeverityLevel,
            CssValidatorWrapperConfiguration::CONFIG_KEY_URL_TO_VALIDATE => $this->webResource->getUrl(),
            CssValidatorWrapperConfiguration::CONFIG_KEY_CONTENT_TO_VALIDATE => $this->webResource->getContent(),
            CssValidatorWrapperConfiguration::CONFIG_KEY_FLAGS => $cssValidatorFlags,
            CssValidatorWrapperConfiguration::CONFIG_KEY_DOMAINS_TO_IGNORE => $domainsToIgnore,
            CssValidatorWrapperConfiguration::CONFIG_KEY_HTTP_CLIENT => $this->getHttpClientService()->get(),
        ];

        $this->cssValidatorWrapper->createConfiguration($configurationValues);

        $this->cssValidatorWrapper
            ->getConfiguration()
            ->getWebResourceService()
            ->getConfiguration()
            ->enableRetryWithUrlEncodingDisabled();

        $this->getHttpClientService()->setCookies($this->task->getParameter('cookies'));
        $this->getHttpClientService()->setBasicHttpAuthorization(
            $this->task->getParameter('http-auth-username'),
            $this->task->getParameter('http-auth-password')
        );

        $cssValidatorOutput = $this->cssValidatorWrapper->validate();

        $this->getHttpClientService()->clearCookies();
        $this->getHttpClientService()->clearBasicHttpAuthorization();

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

        return $this->serializer->serialize(
            $this->prepareCssValidatorOutput($cssValidatorOutput), 'json'
        );
    }

    private function prepareCssValidatorOutput(CssValidatorOutput $cssValidatorOutput)
    {
        $serializableMessages = [];
        $messages = $cssValidatorOutput->getMessages();

        foreach ($messages as $index => $message) {
            /* @var $message CssValidatorOutputMessage */

            if ($message->isError()) {
                /* @var $message CssValidatorOutputError */
                if ($this->isCssValidatorHttpError($message)) {
                    $message->setMessage('http-retrieval-' . $this->getCssValidatorHttpErrorStatusCode($message));
                }

                if ($this->isCssValidatorCurlError($message)) {
                    $message->setMessage('http-retrieval-curl-code-' . $this->getCssValidatorCurlErrorCode($message));
                }
            }

            $serializableMessages[] = [
                'message' => $message->getMessage(),
                'context' => $message->getContext(),
                'line_number' => $message->getLineNumber(),
                'type' => $message->getSerializedType(),
                'ref' => $message->getRef()
            ];
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
