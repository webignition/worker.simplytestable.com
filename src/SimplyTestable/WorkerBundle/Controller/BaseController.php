<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\HttpFoundation\ParameterBag;
use SimplyTestable\WorkerBundle\Services\WorkerService;

abstract class BaseController extends Controller
{
    /**
     *
     * @var ParameterBag
     */
    private $arguments;

    /**
     *
     * @var array
     */
    private $inputDefinitions = array();


    /**
     *
     * @var array
     */
    private $requestTypes = array();


    /**
     * Set collection of InputDefinition objects
     * key is controller method name
     * value is InputDefinition
     *
     * @param array $inputDefinition Collection of InputDefintions
     */
    protected function setInputDefinitions($inputDefinitions) {
        $this->inputDefinitions = $inputDefinitions;
    }

    /**
     *
     * @param mixed $object
     * @param int statusCode
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function sendResponse($object = null, $statusCode = 200) {
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
     *
     * @param int $requestType
     * @return \SimplyTestable\WorkerBundle\Controller\ApiController
     */
    protected function setRequestTypes($requestTypes) {
        $this->requestTypes = $requestTypes;
        return $this;
    }


    /**
     * @param string $methodName
     * @return ParameterBag
     */
    public function getArguments($methodName) {
        if (is_null($this->arguments)) {
            if ($this->getRequestType($methodName) === 'POST') {
                $this->arguments = $this->get('request')->request;
            } else {
                $this->arguments = $this->get('request')->query;
            }
        }

        return $this->arguments;
    }


    /**
     * @param string $methodName
     * @return InputDefinition
     */
    public function getInputDefinition($methodName) {
        if (!isset($this->inputDefinitions[$methodName])) {
            return new InputDefinition();
        }

        return $this->inputDefinitions[$methodName];
    }


    /**
     *
     * @param string $methodName
     * @return string
     */
    private function getRequestType($methodName) {
        if (!is_array($this->requestTypes)) {
            return 'GET';
        }

        if (!isset($this->requestTypes[$methodName])) {
            return 'GET';
        }

        return $this->requestTypes[$methodName];
    }


    /**
     *
     * @param string $methodName
     * @return Response
     */
    public function sendMissingRequiredArgumentResponse($methodName) {
        return $this->sendResponse($this->getInputDefinition($methodName));
    }


    /**
     *
     * @return Response
     */
    public function sendSuccessResponse() {
        return $this->sendResponse();
    }


    /**
     *
     * @return Response
     */
    public function sendFailureResponse() {
        return $this->sendResponse(null, 400);
    }


    /**
     *
     * @return Response
     */
    public function sendServiceUnavailableResponse() {
        return $this->sendResponse(null, 503);
    }


    /**
     *
     * @return boolean
     */
    protected function isInMaintenanceReadOnlyMode() {
        return $this->getWorkerService()->isMaintenanceReadOnly();
    }

    /**
     *
     * @return WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
}
