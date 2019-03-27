<?php

namespace App\Request\Task;

use App\Model\Task\TypeInterface;

class CreateRequest
{
    private $url;
    private $taskType;
    private $parameters;

    public function __construct(string $url, TypeInterface $taskType, string $parameters)
    {
        $this->url = trim($url);
        $this->taskType = $taskType;
        $this->parameters = trim($parameters);
    }

    public function isValid(): bool
    {
        if (empty($this->url)) {
            return false;
        }

        return true;
    }

    public function getTaskType(): TypeInterface
    {
        return $this->taskType;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getParameters(): string
    {
        return $this->parameters;
    }
}
