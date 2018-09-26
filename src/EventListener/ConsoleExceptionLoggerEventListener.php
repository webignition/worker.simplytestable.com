<?php

namespace App\EventListener;

use Symfony\Component\Console\Event\ConsoleErrorEvent;

class ConsoleExceptionLoggerEventListener extends AbstractExceptionLoggerEventListener
{
    public function onConsoleError(ConsoleErrorEvent $event)
    {
        $command = $event->getCommand();
        $input = $event->getInput();
        $error = $event->getError();

        $this->logger->error(
            sprintf(
                '%s',
                $command->getName()
            ),
            [
                'args' => $input->getArguments(),
                'options' => $input->getOptions(),
                'trace' => $error->getTrace()[0],
            ]
        );

        $event->stopPropagation();
    }
}
