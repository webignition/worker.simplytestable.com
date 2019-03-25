<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Services;

use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;

class ContentTypeFactory
{
    public function createContentType(string $contentTypeString): InternetMediaTypeInterface
    {
        $contentTypeParser = new ContentTypeParser();
        $contentTypeParser->setAttemptToRecoverFromInvalidInternalCharacter(true);
        $contentTypeParser->setIgnoreInvalidAttributes(true);

        /** @noinspection PhpUnhandledExceptionInspection */
        $contentType = $contentTypeParser->parse($contentTypeString);

        if (!$contentType instanceof InternetMediaTypeInterface) {
            throw new \RuntimeException();
        }

        return $contentType;
    }
}
