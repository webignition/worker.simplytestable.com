<?php

namespace App\Services;

use App\Model\Source;
use Psr\Http\Message\ResponseInterface;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
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

    public function createOutputMessageCollection(array $messages): array
    {
        return [
            'messages' => $messages,
        ];
    }

    public function createOutputMessage(string $message, string $messageId): array
    {
        return [
            'message' => $message,
            'messageId' => $messageId,
            'type' => 'error',
        ];
    }

    public function createHttpExceptionOutputMessageCollection(string $message, int $statusCode): array
    {
        return $this->createOutputMessageCollection([
            $this->createOutputMessage($message, 'http-retrieval-' . $statusCode),
        ]);
    }

    public function createTransportExceptionOutputMessageCollection(TransportException $transportException): array
    {
        return $this->createOutputMessageCollection([
            $this->createOutputMessage(
                $this->createTransportExceptionOutputMessage($transportException),
                $this->createTransportExceptionOutputMessageId($transportException)
            )
        ]);
    }

    public function createOutputMessageCollectionFromSource(Source $source)
    {
        $message = '';
        $messageId = '';

        $context = $source->getContext();

        $isRedirectLoop = isset($context['is_redirect_loop']) && $context['is_redirect_loop'];
        $isTooManyRedirects = isset($context['too_many_redirects']) && $context['too_many_redirects']
            && !$isRedirectLoop;

        if ($isTooManyRedirects) {
            $message = 'Redirect limit reached';
            $messageId = 'http-retrieval-redirect-limit-reached';
        }

        if ($isRedirectLoop) {
            $message = 'Redirect loop detected';
            $messageId = 'http-retrieval-redirect-loop';
        }

        if (!$messageId) {
            $messageId = 'http-retrieval-';

            $failureType = $source->getFailureType();

            if (Source::FAILURE_TYPE_CURL === $failureType) {
                $message = $this->createMessageFromCurlCode($source->getFailureCode());
                $messageId .= 'curl-code-';
            }

            if (Source::FAILURE_TYPE_UNKNOWN === $failureType) {
                $messageId .= 'unknown-';
            }

            $messageId .= $source->getFailureCode();
        }

        return $this->createOutputMessageCollection([
            $this->createOutputMessage($message, $messageId)
        ]);
    }

    private function createTransportExceptionOutputMessage(TransportException $transportException): string
    {
        if ($transportException->isTooManyRedirectsException()) {
            return $this->isRedirectLoopException()
                ? 'Redirect loop detected'
                : 'Redirect limit reached';
        }

        if ($transportException->isCurlException()) {
            return $this->createMessageFromCurlCode($transportException->getCode());
        }

        return '';
    }

    private function createMessageFromCurlCode(int $code)
    {
        if (self::CURL_CODE_TIMEOUT == $code) {
            return 'Timeout reached retrieving resource';
        }

        if (self::CURL_CODE_DNS_LOOKUP_FAILURE == $code) {
            return 'DNS lookup failure resolving resource domain name';
        }

        if (self::CURL_CODE_INVALID_URL == $code) {
            return 'Invalid resource URL';
        }

        return '';
    }

    private function createTransportExceptionOutputMessageId(TransportException $transportException): string
    {
        $prefix = 'http-retrieval-';

        if ($transportException->isTooManyRedirectsException()) {
            return $this->isRedirectLoopException()
                ? $prefix . 'redirect-loop'
                : $prefix . 'redirect-limit-reached';
        }

        return $prefix . 'curl-code-' . $transportException->getCode();
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
}
