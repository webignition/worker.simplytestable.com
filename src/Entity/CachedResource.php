<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\Table(
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="hash_url_unique", columns={"urlHash"})
 *    }
 * )
 */
class CachedResource
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $url = '';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    private $urlHash;

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

    public static function create(string $url, string $contentType, $body = ''): CachedResource
    {
        if (null === $body) {
            $body = '';
        }

        $cachedResouce = new static();
        $cachedResouce->url = $url;
        $cachedResouce->urlHash = static::createUrlHash($url);
        $cachedResouce->contentType = $contentType;
        $cachedResouce->body = $body;

        return $cachedResouce;
    }

    public static function createUrlHash(string $url): string
    {
        return md5($url);
    }

    public function getId(): ?string
    {
        return $this->id;
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
