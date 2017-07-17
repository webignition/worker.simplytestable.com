<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand;
use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;

class MaintenanceController extends AbstractController
{
    /**
     * @param EnableReadOnlyCommand $enableReadOnlyCommand
     *
     * @return JsonResponse
     */
    public function enableReadOnlyAction(EnableReadOnlyCommand $enableReadOnlyCommand)
    {
        return $this->executeCommand($enableReadOnlyCommand);
    }

    /**
     * @param DisableReadOnlyCommand $disableReadOnlyCommand
     *
     * @return JsonResponse
     */
    public function disableReadOnlyAction(DisableReadOnlyCommand $disableReadOnlyCommand)
    {
        return $this->executeCommand($disableReadOnlyCommand);
    }

    /**
     * @param PerformEnqueueCommand $performEnqueueCommand
     *
     * @return JsonResponse
     */
    public function taskPerformEnqueueAction(PerformEnqueueCommand $performEnqueueCommand)
    {
        return $this->executeCommand($performEnqueueCommand);
    }

    /**
     * @param DisableReadOnlyCommand $disableReadOnlyCommand
     * @param ReportCompletionEnqueueCommand $reportCompletionEnqueueCommand
     * @param PerformEnqueueCommand $performEnqueueCommand
     *
     * @return JsonResponse
     */
    public function leaveReadOnlyAction(
        DisableReadOnlyCommand $disableReadOnlyCommand,
        ReportCompletionEnqueueCommand $reportCompletionEnqueueCommand,
        PerformEnqueueCommand $performEnqueueCommand
    ) {
        $commands = [
            $disableReadOnlyCommand,
            $reportCompletionEnqueueCommand,
            $performEnqueueCommand
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

        return new JsonResponse($responseLines);
    }

    /**
     * @param Command $command
     *
     * @return JsonResponse
     */
    private function executeCommand(Command $command)
    {
        $output = new BufferedOutput();
        $commandResponse = $command->run(new ArrayInput([]), $output);

        $outputLines = explode("\n", trim($output->fetch()));

        return new JsonResponse($outputLines, $commandResponse === 0 ? 200 : 500);
    }
}
