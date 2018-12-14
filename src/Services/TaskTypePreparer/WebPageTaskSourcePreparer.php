<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\CachedResourceManager;
use App\Services\HttpClientConfigurationService;
use App\Services\SourceFactory;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResourceInterfaces\WebResourceInterface;

class WebPageTaskSourcePreparer implements TaskPreparerInterface
{
    const USER_AGENT = 'ST Web Page Task Source Preparer (http://bit.ly/RlhKCL)';

    /**
     * @var int
     */
    private $priority;

    /**
     * @var WebResourceRetriever
     */
    private $webResourceRetriever;

    /**
     * @var HttpClientConfigurationService
     */
    private $httpClientConfigurationService;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * @var CachedResourceManager
     */
    private $cachedResourceManager;

    /**
     * @var SourceFactory
     */
    private $sourceFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        WebResourceRetriever $webResourceRetriever,
        HttpClientConfigurationService $httpClientConfigurationService,
        HttpHistoryContainer $httpHistoryContainer,
        CachedResourceManager $cachedResourceManager,
        SourceFactory $sourceFactory,
        EntityManagerInterface $entityManager,
        int $priority
    ) {
        $this->webResourceRetriever = $webResourceRetriever;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->httpHistoryContainer = $httpHistoryContainer;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->sourceFactory = $sourceFactory;
        $this->entityManager = $entityManager;
        $this->priority = $priority;
    }

    public function prepare(Task $task)
    {
        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);
        $taskUrl = $task->getUrl();

        $source = null;

        try {
            $webPage = $this->webResourceRetriever->retrieve(new Request('GET', $taskUrl));

            // handle creation of cached resource when one already exists

            $cachedResource = CachedResource::create(
                $taskUrl,
                (string)$webPage->getContentType(),
                $webPage->getContent()
            );

            $this->entityManager->persist($cachedResource);
            $this->entityManager->flush();

            $source = $this->sourceFactory->fromCachedResource($cachedResource);
        } catch (InvalidResponseContentTypeException $invalidResponseContentTypeException) {
            $source = $this->sourceFactory->createInvalidSource($taskUrl, Source::MESSAGE_INVALID_CONTENT_TYPE);
        } catch (HttpException $httpException) {
            // 301 could occur here
            // handle differentiating between redirect limit hit and redirect loop

            $source = $this->sourceFactory->createHttpFailedSource(
                $taskUrl,
                $httpException->getCode()
            );
        } catch (TransportException $transportException) {
            if (!$transportException->isCurlException() && !$transportException->isTooManyRedirectsException()) {
                $source = $this->sourceFactory->createUnknownFailedSource($taskUrl);
            } else {
                $source = $this->sourceFactory->createCurlFailedSource(
                    $taskUrl,
                    $transportException->getCode()
                );
            }
        } catch (InternetMediaTypeParseException $e) {
            $source = $this->sourceFactory->createInvalidSource(
                $taskUrl,
                Source::MESSAGE_INVALID_CONTENT_TYPE
            );
        }

        $task->addSource($source);
    }

    public function handles(string $taskType): bool
    {
        return in_array($taskType, [
            TypeInterface::TYPE_HTML_VALIDATION,
            TypeInterface::TYPE_CSS_VALIDATION,
            TypeInterface::TYPE_LINK_INTEGRITY,
            TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL,
            TypeInterface::TYPE_URL_DISCOVERY,
        ]);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param string $url
     * @return WebResourceInterface
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     * @throws HttpException
     */
    private function retrieveWebPage(string $url)
    {
        $request = new Request('GET', $url);

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
}
