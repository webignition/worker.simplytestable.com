<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\HttpClientConfigurationService;
use App\Services\RequestIdentifierFactory;
use App\Services\SourceFactory;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;

class WebPageTaskSourcePreparer implements TaskPreparerInterface
{
    const USER_AGENT = 'ST Web Page Task Source Preparer (http://bit.ly/RlhKCL)';

    /**
     * @var int
     */
    private $priority;

    private $webResourceRetriever;
    private $httpClientConfigurationService;
    private $httpHistoryContainer;
    private $cachedResourceManager;
    private $sourceFactory;
    private $entityManager;
    private $requestIdentifierFactory;
    private $cachedResourceFactory;

    public function __construct(
        WebResourceRetriever $webResourceRetriever,
        HttpClientConfigurationService $httpClientConfigurationService,
        HttpHistoryContainer $httpHistoryContainer,
        CachedResourceManager $cachedResourceManager,
        SourceFactory $sourceFactory,
        EntityManagerInterface $entityManager,
        RequestIdentifierFactory $requestIdentifierFactory,
        CachedResourceFactory $cachedResourceFactory,
        int $priority
    ) {
        $this->webResourceRetriever = $webResourceRetriever;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->httpHistoryContainer = $httpHistoryContainer;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->sourceFactory = $sourceFactory;
        $this->entityManager = $entityManager;
        $this->requestIdentifierFactory = $requestIdentifierFactory;
        $this->cachedResourceFactory = $cachedResourceFactory;
        $this->priority = $priority;
    }

    public function prepare(Task $task)
    {
        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);
        $taskUrl = $task->getUrl();

        $source = null;

        try {
            /* @var WebPage $webPage */
            $webPage = $this->webResourceRetriever->retrieve(new Request('GET', $taskUrl));

            $requestIdentifier = $this->requestIdentifierFactory->createFromTask($task);
            $requestHash = (string) $requestIdentifier;

            $cachedResource = $this->cachedResourceManager->find($requestHash);
            if (!$cachedResource) {
                $cachedResource = $this->cachedResourceFactory->createForTask($requestHash, $task, $webPage);

                $this->entityManager->persist($cachedResource);
                $this->entityManager->flush();
            }

            $cachedResource = CachedResource::create(
                $requestIdentifier,
                $taskUrl,
                (string)$webPage->getContentType(),
                $webPage->getContent()
            );

            $source = $this->sourceFactory->fromCachedResource($cachedResource);
        } catch (InvalidResponseContentTypeException $invalidResponseContentTypeException) {
            $source = $this->sourceFactory->createInvalidSource($taskUrl, Source::MESSAGE_INVALID_CONTENT_TYPE);
        } catch (HttpException $httpException) {
            $source = $this->sourceFactory->createHttpFailedSource(
                $taskUrl,
                $httpException->getCode()
            );
        } catch (TransportException $transportException) {
            if (!$transportException->isCurlException() && !$transportException->isTooManyRedirectsException()) {
                $source = $this->sourceFactory->createUnknownFailedSource($taskUrl);
            } else {
                if ($transportException->isTooManyRedirectsException()) {
                    $this->fixHeadRequestMethods();

                    $source = $this->sourceFactory->createHttpFailedSource(
                        $taskUrl,
                        301,
                        [
                            'too_many_redirects' => true,
                            'is_redirect_loop' => $this->httpHistoryContainer->hasRedirectLoop(),
                            'history' => $this->httpHistoryContainer->getRequestUrlsAsStrings(),
                        ]
                    );
                } else {
                    $source = $this->sourceFactory->createCurlFailedSource(
                        $taskUrl,
                        $transportException->getCode()
                    );
                }
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
     * Guzzle currently (incorrectly) follows a redirected HEAD request with a GET request
     * This modifies the method on such requests
     */
    private function fixHeadRequestMethods()
    {
        $httpTransactionCount = $this->httpHistoryContainer->count();
        $httpTransactions = $this->httpHistoryContainer->getTransactions();
        $headTransactions = array_slice($httpTransactions, 0, $httpTransactionCount / 2);

        foreach ($headTransactions as $httpTransaction) {
            /* @var RequestInterface $request */
            $request = $httpTransaction['request'];

            /** @noinspection PhpUnhandledExceptionInspection */
            $reflector = new ReflectionClass(Request::class);
            $property = $reflector->getProperty('method');
            $property->setAccessible(true);
            $property->setValue($request, 'HEAD');
        }
    }
}
