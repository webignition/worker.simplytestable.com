<?php

namespace App\Services\Request\Factory\Task;

use App\Request\Task\CancelRequest;
use App\Request\Task\CancelRequestCollection;
use App\Services\Request\Factory\AbstractPostRequestFactory;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelRequestCollectionFactory extends AbstractPostRequestFactory
{
    const PARAMETER_IDS = 'ids';

    private $cancelRequestFactory;

    /**
     * @param RequestStack $requestStack
     * @param CancelRequestFactory $cancelRequestFactory
     */
    public function __construct(RequestStack $requestStack, CancelRequestFactory $cancelRequestFactory)
    {
        parent::__construct($requestStack);

        $this->cancelRequestFactory = $cancelRequestFactory;
    }

    public function create(): CancelRequestCollection
    {
        $cancelRequests = [];
        $requestTaskIds = $this->requestParameters->get(self::PARAMETER_IDS);

        if (is_array($requestTaskIds)) {
            foreach ($requestTaskIds as $requestTaskId) {
                $this->cancelRequestFactory->setRequestParameters(new ParameterBag([
                    CancelRequestFactory::PARAMETER_ID => $requestTaskId,
                ]));
                $cancelRequest = $this->cancelRequestFactory->create();

                if ($cancelRequest instanceof CancelRequest) {
                    $cancelRequests[] = $cancelRequest;
                }
            }
        }

        return new CancelRequestCollection($cancelRequests);
    }
}
