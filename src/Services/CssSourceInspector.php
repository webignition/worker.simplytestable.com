<?php

namespace App\Services;

use webignition\WebResource\WebPage\WebPage;

class CssSourceInspector
{
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
}
