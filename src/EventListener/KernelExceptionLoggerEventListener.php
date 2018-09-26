<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class KernelExceptionLoggerEventListener extends AbstractExceptionLoggerEventListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return null;
        }

        $exception = $event->getException();

        $this->logger->error(
            sprintf(
                '[%s]: %s',
                get_class($exception),
                $exception->getMessage()
            ),
            [
                'trace' => $exception->getTrace()[0],
            ]
        );

        $responseStatusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $event->setResponse(new Response('', $responseStatusCode));

        return null;
    }
}
