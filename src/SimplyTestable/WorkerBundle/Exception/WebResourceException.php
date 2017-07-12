<?php

namespace SimplyTestable\WorkerBundle\Exception;

use \Exception as BaseException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;

class WebResourceException extends BaseException
{
    /**
     * @var Response
     */
    private $response;

    /**
     *
     * @var Request
     */
    private $request;

    /**
     *
     * @param Response $response
     * @param Request $request
     */
    public function __construct(Response $response, Request $request = null)
    {
        $this->response = $response;
        $this->request = $request;

        parent::__construct($response->getReasonPhrase(), $response->getStatusCode());
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
