<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand;
use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpFoundation\JsonResponse;

class MaintenanceController extends Controller
{
    public function enableReadOnlyAction()
    {
        return $this->executeCommand(new EnableReadOnlyCommand(
            $this->container->get('simplytestable.services.workerservice')
        ));
    }

    public function disableReadOnlyAction()
    {
        return $this->executeCommand($this->createDisableReadOnlyCommand());
    }

    public function taskPerformEnqueueAction()
    {
        return $this->executeCommand(new PerformEnqueueCommand(
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactory')
        ));
    }

    public function leaveReadOnlyAction()
    {
        $commands = [
            $this->createDisableReadOnlyCommand(),
            $this->createReportCompletionEnqueueCommand(),
            $this->createPerformEnqueueCommand()
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
     * @return DisableReadOnlyCommand
     */
    private function createDisableReadOnlyCommand()
    {
        return new DisableReadOnlyCommand(
            $this->container->get('simplytestable.services.workerservice')
        );
    }

    /**
     * @return ReportCompletionEnqueueCommand
     */
    private function createReportCompletionEnqueueCommand()
    {
        return new ReportCompletionEnqueueCommand(
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactory')
        );
    }

    /**
     * @return PerformEnqueueCommand
     */
    private function createPerformEnqueueCommand()
    {
        return new PerformEnqueueCommand(
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactory')
        );
    }

    /**
     * @param Command $command
     *
     * @return JsonResponse
     */
    private function executeCommand(Command $command)
    {
        $output = new StringOutput();
        $commandResponse = $command->run(new ArrayInput([]), $output);

        $outputLines = explode("\n", trim($output->getBuffer()));

        return new JsonResponse($outputLines, $commandResponse === 0 ? 200 : 500);
    }
}
