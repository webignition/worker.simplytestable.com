<?php

namespace SimplyTestable\AppBundle\Services;

use GuzzleHttp\HandlerStack;

class HttpRetryMiddleware
{
    const HANDLER_STACK_KEY = 'retry';

    /**
     * @var callable
     */
    private $httpRetryMiddleware;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @param HttpRetryMiddlewareFactory $httpRetryMiddlewareFactory
     * @param HandlerStack $handlerStack
     */
    public function __construct(HttpRetryMiddlewareFactory $httpRetryMiddlewareFactory, HandlerStack $handlerStack)
    {
        $this->httpRetryMiddleware = $httpRetryMiddlewareFactory->create();
        $this->handlerStack = $handlerStack;
    }

    public function enable()
    {
        $this->handlerStack->push($this->httpRetryMiddleware, self::HANDLER_STACK_KEY);
    }

    public function disable()
    {
        $this->handlerStack->remove(self::HANDLER_STACK_KEY);
    }
}
