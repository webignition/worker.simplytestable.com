<?php

namespace SimplyTestable\WorkerBundle\Services\Request\Factory\Task;

use SimplyTestable\WorkerBundle\Request\Task\CancelRequestCollection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class CancelRequestCollectionFactory
{
    const PARAMETER_IDS = 'ids';

    /**
     * @var ParameterBag
     */
    private $requestParameters;

    /**
     * @var CancelRequestFactory
     */
    private $cancelRequestFactory;

    /**
     * @param Request $request
     * @param CancelRequestFactory $cancelRequestFactory
     */
    public function __construct(Request $request, CancelRequestFactory $cancelRequestFactory)
    {
        $this->requestParameters = $request->request;
        $this->cancelRequestFactory = $cancelRequestFactory;
    }

    /**
     * @return CancelRequestCollection
     */
    public function create()
    {
        $cancelRequests = [];
        $requestTaskIds = $this->requestParameters->get(self::PARAMETER_IDS);

        if (is_array($requestTaskIds)) {
            foreach ($requestTaskIds as $requestTaskId) {
                $this->cancelRequestFactory->setRequestParameters(new ParameterBag([
                    CancelRequestFactory::PARAMETER_ID => $requestTaskId,
                ]));
                $cancelRequest = $this->cancelRequestFactory->create();

                if ($cancelRequest->isValid()) {
                    $cancelRequests[] = $cancelRequest;
                }
            }
        }

        return new CancelRequestCollection($cancelRequests);
    }
}
