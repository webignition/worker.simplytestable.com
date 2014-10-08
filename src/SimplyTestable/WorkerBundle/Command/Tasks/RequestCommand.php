<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException;

class RequestCommand extends Command {

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_FAILED = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;

    protected function configure()
    {
        $this
            ->setName('simplytestable:tasks:request')
            ->setDescription('Request tasks to be assigned by the core application')
            ->setHelp(<<<EOF
Request tasks to be assigned by the core application
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        if ($this->getWorkerService()->isMaintenanceReadOnly()) {
            $this->requeue();
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        try {
            if ($this->getTasksService()->request()) {
                return self::RETURN_CODE_OK;
            }
        } catch (RequestException $requestException) {
        }

        $this->requeue();
        return self::RETURN_CODE_FAILED;
    }


    private function requeue() {
        if ($this->getResqueQueueService()->isEmpty('tasks-request')) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'tasks-request'
                )
            );
        }
    }

}