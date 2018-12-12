<?php

namespace App\Services;

class ApplicationConfiguration
{
    private $hostname;
    private $token;

    public function __construct(string $hostname, string $token)
    {
        $this->hostname = $hostname;
        $this->token = $token;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
