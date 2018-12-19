<?php

namespace App\Model;

class RequestIdentifier
{
    /**
     * @var string
     */
    private $url = '';

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var string
     */
    private $hash = '';

    public function __construct(string $url, array $parameters)
    {
        $this->url = $url;
        $this->parameters = $parameters;
        $this->hash = md5($this->url . json_encode($parameters));
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function __toString()
    {
        return $this->hash;
    }
}
