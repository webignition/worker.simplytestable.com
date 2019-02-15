<?php

namespace App\Model;

class CssSourceUrl
{
    const TYPE_RESOURCE = 'resource';
    const TYPE_IMPORT = 'import';

    private $url;
    private $type;

    public function __construct(string $url, string $type)
    {
        $this->url = $url;
        $this->type = $type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
