<?php

namespace App\Model;

use webignition\UrlHealthChecker\LinkState;

class LinkIntegrityResult implements \JsonSerializable
{
    /**
     * @var string
     */
    private $context;

    /**
     * @var string
     */
    private $url;

    /**
     * @var LinkState
     */
    private $linkState;

    public function __construct(string $url, string $context, LinkState $linkState)
    {
        $this->url = $url;
        $this->context = $context;
        $this->linkState = $linkState;
    }

    public function getLinkState(): LinkState
    {
        return $this->linkState;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function jsonSerialize(): array
    {
        return [
            'context' => $this->context,
            'state' => $this->linkState->getState(),
            'type' => $this->linkState->getType(),
            'url' => $this->url
        ];
    }
}
