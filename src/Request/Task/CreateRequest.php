<?php

namespace App\Request\Task;

use App\Services\TaskTypeValidator;

class CreateRequest
{
    /**
     * @var string
     */
    private $taskType;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $parameters;

    public function __construct(string $taskType, ?string $url, ?string $parameters)
    {
        $this->taskType = $taskType;
        $this->url = trim($url);
        $this->parameters = trim($parameters);
    }

    public function isValid(): bool
    {
        if (!TaskTypeValidator::isValid($this->taskType)) {
            return false;
        }

        if (empty($this->url)) {
            return false;
        }

        return true;
    }

    public function getTaskType(): string
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
