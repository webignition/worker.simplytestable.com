<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand;
use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\CommandService;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceController extends BaseController
{
    public function enableReadOnlyAction()
    {
        return $this->executeCommand(EnableReadOnlyCommand::class);
    }

    public function disableReadOnlyAction()
    {
        return $this->executeCommand(DisableReadOnlyCommand::class);
    }

    public function taskPerformEnqueueAction()
    {
        return $this->executeCommand(PerformEnqueueCommand::class);
    }

    public function leaveReadOnlyAction()
    {
        $commands = [
            DisableReadOnlyCommand::class,
            ReportCompletionEnqueueCommand::class,
            PerformEnqueueCommand::class
        ];

        $responseLines = array();

        foreach ($commands as $command) {
            $response = $this->executeCommand($command);
            $rawResponseLines =  json_decode($response->getContent());
            foreach ($rawResponseLines as $rawResponseLine) {
                if (trim($rawResponseLine) != '') {
                    $responseLines[] = trim($rawResponseLine);
                }
            }
        }

        return $this->sendResponse($responseLines);
    }

    /**
     * @param string $commandClass
     * @param array $inputArray
     *
     * @return Response
     */
    private function executeCommand($commandClass, $inputArray = array())
    {
        $output = new StringOutput();
        $commandResponse =  $this->getCommandService()->execute(
                $commandClass,
                $inputArray,
                $output
        );

        $outputLines = explode("\n", trim($output->getBuffer()));

        return $this->sendResponse($outputLines, $commandResponse === 0 ? 200 : 500);
    }

    /**
     * @return CommandService
     */
    private function getCommandService()
    {
        return $this->container->get('simplytestable.services.commandService');
    }
}
