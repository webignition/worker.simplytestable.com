<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;

abstract class AbstractExceptionLoggerEventListener
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
