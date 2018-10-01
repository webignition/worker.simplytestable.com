<?php

namespace App\Request\Task;

use App\Model\Task\Type;

class CreateRequest
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var Type
     */
    private $taskType;

    /**
     * @var string
     */
    private $parameters;

    public function __construct(string $url, ?Type $taskType, ?string $parameters)
    {
        $this->url = trim($url);
        $this->taskType = $taskType;
        $this->parameters = trim($parameters);
    }

    public function isValid(): bool
    {
        if (empty($this->taskType)) {
            return false;
        }

        if (empty($this->url)) {
            return false;
        }

        return true;
    }

    public function getTaskType(): Type
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
