<?php

namespace App\Services;

use App\Model\CssSourceUrl;
use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\Property\Charset;
use Sabberworm\CSS\Property\Import;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\CssValidatorWrapper\SourceInspector as WrapperCssSourceInspector;
use webignition\Uri\Uri;
use webignition\WebResource\WebPage\WebPage;

class CssSourceInspector
{
    private $wrapperCssSourceInspector;

    public function __construct(WrapperCssSourceInspector $wrapperCssSourceInspector)
    {
        $this->wrapperCssSourceInspector = $wrapperCssSourceInspector;
    }

    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    public function findStyleBlocks(WebPage $webPage): array
    {
        $styleBlocks = [];

        $inspector = $webPage->getInspector();

        /* @var \DOMElement[] $styleElements */
        $styleElements = $inspector->querySelectorAll('style');

        foreach ($styleElements as $styleElement) {
            $content = trim($styleElement->textContent);

            if ('' !== $content && !in_array($content, $styleBlocks)) {
                $styleBlocks[] = $content;
            }
        }

        return $styleBlocks;
    }

    /**
     * @param string $css
     *
     * @return string[]
     */
    public function findImportValues(string $css): array
    {
        $importValues = [];

        $cssParser = new CssParser($css);

        $cssDocument = $cssParser->parse();
        $cssContents = $cssDocument->getContents();

        $nonImportFound = false;

        foreach ($cssContents as $item) {
            if ($item instanceof Import && !$nonImportFound) {
                $value = $item->getLocation()->getURL()->getString();

                if (!in_array($value, $importValues)) {
                    $importValues[] = $value;
                }
            }

            if (!$item instanceof Import && !$item instanceof Charset) {
                $nonImportFound = true;
            }
        }

        return $importValues;
    }

    /**
     * @param string[] $importValues
     * @param string $baseUrl
     *
     * @return string[]
     */
    public function createImportUrls(array $importValues, string $baseUrl): array
    {
        $urls = [];

        $baseUri = new Uri($baseUrl);

        foreach ($importValues as $value) {
            $relativeUri = new Uri($value);

            $urls[] = (string) AbsoluteUrlDeriver::derive($baseUri, $relativeUri);
        }

        return $urls;
    }

    /**
     * @param string $css
     * @param string $baseUrl
     *
     * @return string[]
     */
    public function findCssImportUrls(string $css, string $baseUrl): array
    {
        $importValues = $this->findImportValues($css);

        return $this->createImportUrls($importValues, $baseUrl);
    }

    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    public function findLinkElementStylesheetUrls(WebPage $webPage)
    {
        return $this->wrapperCssSourceInspector->findStylesheetUrls($webPage);
    }

    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    public function findWebPageImportUrls(WebPage $webPage): array
    {
        $urls = [];

        $styleBlocks = $this->findStyleBlocks($webPage);
        $baseUrl = (string) $webPage->getBaseUrl();

        foreach ($styleBlocks as $styleBlock) {
            $importValues = $this->findImportValues($styleBlock);

            $urls = array_merge($urls, $this->createImportUrls($importValues, $baseUrl));
        }

        return $urls;
    }

    /**
     * @param WebPage $webPage
     *
     * @return CssSourceUrl[]
     */
    public function findStylesheetUrls(WebPage $webPage): array
    {
        $resourceUrls = $this->findLinkElementStylesheetUrls($webPage);
        $importUrls = $this->findWebPageImportUrls($webPage);

        $cssSourceUrls = [];

        foreach ($resourceUrls as $resourceUrl) {
            if (!array_key_exists($resourceUrl, $cssSourceUrls)) {
                $cssSourceUrls[$resourceUrl] = new CssSourceUrl($resourceUrl, CssSourceUrl::TYPE_RESOURCE);
            }
        }

        foreach ($importUrls as $importUrl) {
            if (!array_key_exists($importUrl, $cssSourceUrls)) {
                $cssSourceUrls[$importUrl] = new CssSourceUrl($importUrl, CssSourceUrl::TYPE_IMPORT);
            }
        }

        return $cssSourceUrls;
    }
}
