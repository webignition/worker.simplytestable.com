<?php

namespace App\Request\Task;

class CreateRequestCollection
{
    /**
     * @var CreateRequest[]
     */
    private $createRequests;

    /**
     * @param CreateRequest[] $createRequests
     */
    public function __construct(array $createRequests)
    {
        $this->createRequests = $createRequests;
    }

    /**
     * @return CreateRequest[]
     */
    public function getCreateRequests(): array
    {
        return $this->createRequests;
    }
}
