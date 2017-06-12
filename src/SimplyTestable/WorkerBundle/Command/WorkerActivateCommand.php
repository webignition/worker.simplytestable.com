<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerActivateCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_UNKNOWN_ERROR = -2;
    const RETURN_CODE_FAILED_DUE_TO_WRONG_STATE = -3;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:worker:activate')
            ->setDescription(
                'Activate this worker, making it known to all core application instance of which it is aware'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getWorkerService()->isMaintenanceReadOnly()) {
            $output->writeln('Unable to activate, worker application is in maintenance read-only mode');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $activationResult = $this->getWorkerService()->activate();
        if ($activationResult === 0) {
            return 0;
        }

        if ($activationResult === 1) {
            $output->writeln('Activation failed, unknown error');
            return self::RETURN_CODE_UNKNOWN_ERROR;
        }

        if ($this->isHttpStatusCode($activationResult)) {
            $output->writeln('Activation failed, HTTP response '.$activationResult);
        } else {
            $output->writeln('Activation failed, CURL error '.$activationResult);
        }

        return $activationResult;
    }
}
