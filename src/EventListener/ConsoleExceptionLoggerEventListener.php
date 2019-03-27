<?php

namespace App\EventListener;

use Symfony\Component\Console\Event\ConsoleErrorEvent;

class ConsoleExceptionLoggerEventListener extends AbstractExceptionLoggerEventListener
{
    public function onConsoleError(ConsoleErrorEvent $event)
    {
        $command = $event->getCommand();

        if ($command) {
            $input = $event->getInput();
            $error = $event->getError();

            if (!substr_count(get_class($error), 'Symfony\Component\Console')) {
                $this->logger->error(
                    $command->getName(),
                    [
                        'args' => $input->getArguments(),
                        'options' => $input->getOptions(),
                        'trace' => $error->getTrace()[0],
                    ]
                );

                $event->stopPropagation();
            }
        }
    }
}
