<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command;

use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class ConsoleCommandBaseTestCase extends BaseSimplyTestableTestCase
{
    const CONSOLE_COMMAND_SUCCESS = 0;
    const CONSOLE_COMMAND_FAILURE = 1;

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    protected function executeCommand($name, $arguments = [])
    {
        $command = $this->application->find($name);
        $commandTester = new CommandTester($command);

        $arguments['command'] = $command->getName();

        return $commandTester->execute($arguments);
    }
}
