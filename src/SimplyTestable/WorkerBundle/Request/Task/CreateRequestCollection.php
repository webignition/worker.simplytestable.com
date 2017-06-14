<?php

namespace SimplyTestable\WorkerBundle\Request\Task;

class CreateRequestCollection
{
    /**
     * @var CreateRequest[]
     */
    private $createRequests;

    /**
     * @param CreateRequest[]
     */
    public function __construct($createRequests)
    {
        $this->createRequests = $createRequests;
    }

    /**
     * @return CreateRequest[]
     */
    public function getCreateRequests()
    {
        return $this->createRequests;
    }
}
