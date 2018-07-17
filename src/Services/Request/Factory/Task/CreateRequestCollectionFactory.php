<?php

namespace App\Services\Request\Factory\Task;

use App\Request\Task\CreateRequestCollection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateRequestCollectionFactory
{
    const PARAMETER_TASKS = 'tasks';

    /**
     * @var ParameterBag
     */
    private $requestParameters;

    /**
     * @var CreateRequestFactory
     */
    private $createRequestFactory;

    /**
     * @param RequestStack $requestStack
     * @param CreateRequestFactory $createRequestFactory
     */
    public function __construct(RequestStack $requestStack, CreateRequestFactory $createRequestFactory)
    {
        $this->requestParameters = $requestStack->getCurrentRequest()->request;
        $this->createRequestFactory = $createRequestFactory;
    }

    /**
     * @return CreateRequestCollection
     */
    public function create()
    {
        $createRequests = [];
        $requestTasks = $this->requestParameters->get(self::PARAMETER_TASKS);

        if (is_array($requestTasks)) {
            foreach ($requestTasks as $requestTask) {
                $this->createRequestFactory->setRequestParameters(new ParameterBag($requestTask));
                $createRequest = $this->createRequestFactory->create();

                if ($createRequest->isValid()) {
                    $createRequests[] = $createRequest;
                }
            }
        }

        return new CreateRequestCollection($createRequests);
    }
}
