<?php

namespace App\Services;

use App\Entity\Task\Task;
use App\Model\Source;
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
use webignition\WebResource\WebResource;

class TaskSourceRetriever
{
    const USER_AGENT = 'ST Task Source Retriever (http://bit.ly/RlhKCL)';

    private $httpClientConfigurationService;
    private $httpHistoryContainer;
    private $cachedResourceManager;
    private $sourceFactory;
    private $entityManager;
    private $requestIdentifierFactory;
    private $cachedResourceFactory;

    public function __construct(
        HttpClientConfigurationService $httpClientConfigurationService,
        HttpHistoryContainer $httpHistoryContainer,
        CachedResourceManager $cachedResourceManager,
        SourceFactory $sourceFactory,
        EntityManagerInterface $entityManager,
        RequestIdentifierFactory $requestIdentifierFactory,
        CachedResourceFactory $cachedResourceFactory
    ) {
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->httpHistoryContainer = $httpHistoryContainer;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->sourceFactory = $sourceFactory;
        $this->entityManager = $entityManager;
        $this->requestIdentifierFactory = $requestIdentifierFactory;
        $this->cachedResourceFactory = $cachedResourceFactory;
    }

    public function retrieve(
        WebResourceRetriever $webResourceRetriever,
        Task $task,
        string $url,
        array $sourceContext = []
    ) {
        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        $existingSources = $task->getSources();
        if (array_key_exists($url, $existingSources)) {
            return;
        }

        $source = null;

        try {
            /* @var WebResource $resource */
            $resource = $webResourceRetriever->retrieve(new Request('GET', $url));

            $requestIdentifier = $this->requestIdentifierFactory->createFromTaskResource($task, $url);
            $requestHash = (string) $requestIdentifier;

            $cachedResource = $this->cachedResourceManager->find($requestHash);
            if (!$cachedResource) {
                $cachedResource = $this->cachedResourceFactory->create($requestHash, $url, $resource);

                $this->entityManager->persist($cachedResource);
                $this->entityManager->flush();
            }

            $source = $this->sourceFactory->fromCachedResource($cachedResource, $sourceContext);
        } catch (InvalidResponseContentTypeException $invalidResponseContentTypeException) {
            $source = $this->sourceFactory->createInvalidSource(
                $url,
                sprintf(
                    Source::MESSAGE_INVALID_CONTENT_TYPE,
                    (string) $invalidResponseContentTypeException->getContentType()
                )
            );
        } catch (HttpException $httpException) {
            $source = $this->sourceFactory->createHttpFailedSource($url, $httpException->getCode());
        } catch (TransportException $transportException) {
            if (!$transportException->isCurlException() && !$transportException->isTooManyRedirectsException()) {
                $source = $this->sourceFactory->createUnknownFailedSource($url);
            } else {
                if ($transportException->isTooManyRedirectsException()) {
                    $this->fixHeadRequestMethods();

                    $source = $this->sourceFactory->createHttpFailedSource(
                        $url,
                        301,
                        [
                            'too_many_redirects' => true,
                            'is_redirect_loop' => $this->httpHistoryContainer->hasRedirectLoop(),
                            'history' => $this->httpHistoryContainer->getRequestUrlsAsStrings(),
                        ]
                    );
                } else {
                    $source = $this->sourceFactory->createCurlFailedSource($url, $transportException->getCode());
                }
            }
        } catch (InternetMediaTypeParseException $e) {
            $source = $this->sourceFactory->createInvalidSource(
                $url,
                sprintf(Source::MESSAGE_INVALID_CONTENT_TYPE, $e->getContentTypeString())
            );
        }

        $task->addSource($source);
        $this->entityManager->persist($task);
        $this->entityManager->flush();
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
            /** @noinspection PhpUnhandledExceptionInspection */
            $property = $reflector->getProperty('method');
            $property->setAccessible(true);
            $property->setValue($request, 'HEAD');
        }
    }
}
