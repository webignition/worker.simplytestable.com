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
     * @var CachedResource|string
     *
     * @ORM\Column(type="blob")
     */
    private $body = '';

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setBody(string $body)
    {
        $this->body = $body;
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
