<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class CachedResource
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private $requestHash = '';

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $url = '';

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $contentType = '';

    /**
     * @var CachedResource|string
     *
     * @ORM\Column(type="blob")
     */
    private $body = '';

    public static function create(string $requestHash, string $url, string $contentType, $body = ''): CachedResource
    {
        if (null === $body) {
            $body = '';
        }

        $cachedResouce = new static();
        $cachedResouce->requestHash = $requestHash;
        $cachedResouce->url = $url;
        $cachedResouce->contentType = $contentType;
        $cachedResouce->body = $body;

        return $cachedResouce;
    }

    public function getRequestHash(): string
    {
        return $this->requestHash;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return resource
     */
    public function getBody()
    {
        $body = $this->body;

        if (!is_resource($body)) {
            $body = $this->createStreamFromString($body);
        }

        return $body;
    }

    /**
     * @param string $content
     *
     * @return CachedResource
     */
    private function createStreamFromString(string $content)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }
}
