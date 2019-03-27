<?php

namespace App\Services;

use App\Model\Source;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

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
                $message = $this->createMessageFromCurlCode((int) $source->getFailureCode());
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

    public function createInvalidCharacterEncodingOutput(string $encoding)
    {
        return $this->createOutputMessageCollection([
            $this->createOutputMessage($encoding, 'invalid-character-encoding'),
        ]);
    }

    private function createOutputMessageCollection(array $messages): array
    {
        return [
            'messages' => $messages,
        ];
    }

    private function createOutputMessage(string $message, string $messageId): array
    {
        return [
            'message' => $message,
            'messageId' => $messageId,
            'type' => 'error',
        ];
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
}
