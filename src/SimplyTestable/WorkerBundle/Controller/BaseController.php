<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\WorkerBundle\Services\WorkerService;

abstract class BaseController extends Controller
{
    /**
     * @param mixed $object
     * @param int $statusCode
     *
     * @return Response
     */
    protected function sendResponse($object = null, $statusCode = 200)
    {
        if (is_null($object)) {
            $response = new Response();
            $response->setStatusCode($statusCode);

            return $response;
        }

        $response = new Response($this->container->get('jms_serializer')->serialize($object, 'json'));
        $response->setStatusCode($statusCode);
        $response->headers->set('content-type', 'application/json');

        return $response;
    }

    /**
     * @return Response
     */
    public function sendSuccessResponse()
    {
        return $this->sendResponse();
    }

    /**
     * @return Response
     */
    public function sendFailureResponse()
    {
        return $this->sendResponse(null, 400);
    }

    /**
     * @return Response
     */
    public function sendServiceUnavailableResponse()
    {
        return $this->sendResponse(null, 503);
    }

    /**
     * @return boolean
     */
    protected function isInMaintenanceReadOnlyMode()
    {
        return $this->getWorkerService()->isMaintenanceReadOnly();
    }

    /**
     * @return WorkerService
     */
    private function getWorkerService()
    {
        return $this->container->get('simplytestable.services.workerservice');
    }
}
