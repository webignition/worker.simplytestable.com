<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\CommandService;

class MaintenanceController extends BaseController
{

    public function enableReadOnlyAction()
    {
        return $this->executeCommand('SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand');
    }

    public function disableReadOnlyAction() {
        return $this->executeCommand('SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand');
    }

    public function taskPerformEnqueueAction() {
        return $this->executeCommand('SimplyTestable\WorkerBundle\Command\TaskPerformEnqueueCommand');
    }

    public function leaveReadOnlyAction() {
        $commands = array(
            'SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand',
            'SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand',
            'SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand'
        );

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


    private function executeCommand($commandClass, $inputArray = array()) {
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
     *
     * @return CommandService
     */
    private function getCommandService() {
        return $this->container->get('simplytestable.services.commandService');
    }

}
