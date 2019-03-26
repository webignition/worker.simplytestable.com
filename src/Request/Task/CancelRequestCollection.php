<?php

namespace App\Request\Task;

class CancelRequestCollection
{
    /**
     * @var CancelRequest[]
     */
    private $cancelRequests;

    /**
     * @param CancelRequest[] $cancelRequests
     */
    public function __construct(array $cancelRequests)
    {
        $this->cancelRequests = $cancelRequests;
    }

    /**
     * @return CancelRequest[]
     */
    public function getCancelRequests(): array
    {
        return $this->cancelRequests;
    }
}
