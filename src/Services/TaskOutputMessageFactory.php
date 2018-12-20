<?php

namespace App\Services;

use Psr\Http\Message\ResponseInterface;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\TransportException;

class TaskOutputMessageFactory
{
    const CURL_CODE_INVALID_URL = 3;
    const CURL_CODE_TIMEOUT = 28;
    const CURL_CODE_DNS_LOOKUP_FAILURE = 6;

    private $httpHistoryContainer;

    public function __construct(HttpHistoryContainer $httpHistoryContainer)
    {
        $this->httpHistoryContainer = $httpHistoryContainer;
    }

    public function createOutputMessageCollectionFromExceptions(
        ?HttpException $httpException,
        ?TransportException $transportException
    ): \stdClass {
        return (object)[
            'messages' => [
                [
                    'message' => $this->createOutputMessage($httpException, $transportException),
                    'messageId' => 'http-retrieval-' . $this->createOutputMessageId(
                        $httpException,
                        $transportException
                    ),
                    'type' => 'error',
                ]
            ],
        ];
    }

    private function createOutputMessage(
        ?HttpException $httpException,
        ?TransportException $transportException = null
    ): string {
        if ($this->hasTooManyRedirectsException($transportException)) {
            if ($this->isRedirectLoopException()) {
                return 'Redirect loop detected';
            }

            return 'Redirect limit reached';
        }

        if (!empty($transportException)) {
            if ($transportException->isCurlException()) {
                if (self::CURL_CODE_TIMEOUT == $transportException->getCode()) {
                    return 'Timeout reached retrieving resource';
                }

                if (self::CURL_CODE_DNS_LOOKUP_FAILURE == $transportException->getCode()) {
                    return 'DNS lookup failure resolving resource domain name';
                }

                if (self::CURL_CODE_INVALID_URL == $transportException->getCode()) {
                    return 'Invalid resource URL';
                }

                return '';
            }

            return '';
        }

        return $httpException->getMessage();
    }

    private function createOutputMessageId(
        ?HttpException $httpException,
        ?TransportException $transportException
    ): string {
        if ($this->hasTooManyRedirectsException($transportException)) {
            if ($this->isRedirectLoopException()) {
                return 'redirect-loop';
            }

            return 'redirect-limit-reached';
        }

        if (!empty($transportException)) {
            return 'curl-code-' . $transportException->getCode();
        }

        if (!empty($this->curlException)) {
            return 'curl-code-' . $this->curlException->getCurlCode();
        }

        return $httpException->getCode();
    }

    private function isRedirectLoopException(): bool
    {
        /* @var ResponseInterface[] $responses */
        $responses = $this->httpHistoryContainer->getResponses();
        $responseHistoryContainsOnlyRedirects = true;

        foreach ($responses as $response) {
            $statusCode = $response->getStatusCode();

            if ($statusCode < 300 || $statusCode >=400) {
                $responseHistoryContainsOnlyRedirects = false;
            }
        }

        if (!$responseHistoryContainsOnlyRedirects) {
            return false;
        }

        $requestUrls = $this->httpHistoryContainer->getRequestUrlsAsStrings();
        $requestUrls = array_slice($requestUrls, count($requestUrls) / 2);

        foreach ($requestUrls as $urlIndex => $url) {
            if (in_array($url, array_slice($requestUrls, $urlIndex + 1))) {
                return true;
            }
        }

        return false;
    }

    private function hasTooManyRedirectsException(?TransportException $transportException): bool
    {
        if (empty($transportException)) {
            return false;
        }

        return $transportException->isTooManyRedirectsException();
    }
}
