<?php

namespace App\Services;

use App\Model\Source;
use webignition\CssValidatorOutput\Model\ErrorMessage;

class CssValidatorErrorFactory
{
    const HTTP_RETRIEVAL_MESSAGE = 'http-retrieval-%s';

    public function createForUnavailableTaskSource(Source $source)
    {
        $message = 'title';
        $ref = $source->getUrl();

        if ($source->isUnavailable()) {
            $code = Source::FAILURE_TYPE_HTTP === $source->getFailureType()
                ? $source->getFailureCode()
                : 'curl-code-' . $source->getFailureCode();

            $message = sprintf(
                self::HTTP_RETRIEVAL_MESSAGE,
                $code
            );
        }

        if ($source->isInvalidContentType()) {
            $valueParts = explode(':', $source->getValue(), 3);
            $contentTypeString = $valueParts[2];

            $message = 'invalid-content-type:' . $contentTypeString;
        }

        return new ErrorMessage($message, 0, '', $ref);
    }
}
