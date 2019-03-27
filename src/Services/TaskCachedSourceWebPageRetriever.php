<?php

namespace App\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use GuzzleHttp\Psr7\Uri;
use webignition\InternetMediaType\Parameter\Parser\AttributeParserException;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;
use webignition\InternetMediaType\Parser\SubtypeParserException;
use webignition\InternetMediaType\Parser\TypeParserException;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\WebPage\WebPage;

class TaskCachedSourceWebPageRetriever
{
    private $cachedResourceManager;
    private $contentTypeParser;

    public function __construct(
        CachedResourceManager $cachedResourceManager,
        ContentTypeParser $contentTypeParser
    ) {
        $this->cachedResourceManager = $cachedResourceManager;
        $this->contentTypeParser = $contentTypeParser;
    }

    public function retrieve(Task $task): ?WebPage
    {
        $sources = $task->getSources();

        if (!empty($sources)) {
            $primarySource = $sources[$task->getUrl()] ?? null;

            if ($primarySource && $primarySource->isCachedResource()) {
                $requestHash = $primarySource->getValue();
                $cachedResource = $this->cachedResourceManager->find($requestHash);

                if ($cachedResource) {
                    $contentType = $this->getCachedResourceContentType($cachedResource);

                    /* @var WebPage $webPage */
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $webPage = WebPage::createFromContent(
                        (string) stream_get_contents($cachedResource->getBody())
                    );

                    $webPage = $webPage->setContentType($contentType);
                    $webPage = $webPage->setUri(new Uri($task->getUrl()));

                    return $webPage;
                }
            }
        }

        return null;
    }

    private function getCachedResourceContentType(CachedResource $cachedResource): ?InternetMediaTypeInterface
    {
        $contentTypeString = $cachedResource->getContentType();

        try {
            return $this->contentTypeParser->parse($contentTypeString);
        } catch (AttributeParserException $e) {
        } catch (SubtypeParserException $e) {
        } catch (TypeParserException $e) {
        }

        return null;
    }
}
