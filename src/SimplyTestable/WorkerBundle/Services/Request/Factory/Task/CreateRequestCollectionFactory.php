<?php

namespace SimplyTestable\WorkerBundle\Services\Request\Factory\Task;

use SimplyTestable\WorkerBundle\Request\Task\CreateRequestCollection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

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
     * @param Request $request
     * @param CreateRequestFactory $createRequestFactory
     */
    public function __construct(Request $request, CreateRequestFactory $createRequestFactory)
    {
        $this->requestParameters = $request->request;
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
