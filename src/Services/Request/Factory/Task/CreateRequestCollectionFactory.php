<?php

namespace App\Services\Request\Factory\Task;

use App\Request\Task\CreateRequest;
use App\Request\Task\CreateRequestCollection;
use App\Services\Request\Factory\AbstractPostRequestFactory;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateRequestCollectionFactory extends AbstractPostRequestFactory
{
    const PARAMETER_TASKS = 'tasks';

    private $createRequestFactory;

    public function __construct(RequestStack $requestStack, CreateRequestFactory $createRequestFactory)
    {
        parent::__construct($requestStack);

        $this->createRequestFactory = $createRequestFactory;
    }

    public function create(): CreateRequestCollection
    {
        $createRequests = [];
        $requestTasks = $this->requestParameters->get(self::PARAMETER_TASKS);

        if (is_array($requestTasks)) {
            foreach ($requestTasks as $requestTask) {
                $this->createRequestFactory->setRequestParameters(new ParameterBag($requestTask));
                $createRequest = $this->createRequestFactory->create();

                if ($createRequest instanceof CreateRequest && $createRequest->isValid()) {
                    $createRequests[] = $createRequest;
                }
            }
        }

        return new CreateRequestCollection($createRequests);
    }
}
