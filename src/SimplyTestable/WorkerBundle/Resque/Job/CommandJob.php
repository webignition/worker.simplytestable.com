<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use Symfony\Component\Console\Input\ArrayInput;
use CoreSphere\ConsoleBundle\Output\StringOutput;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class CommandJob extends Job {

    /**
     * @return ContainerAwareCommand
     */
    abstract protected function getCommand();


    /**
     * Get the arguments required by the to-be-run command.
     *
     * This may differ from the arguments passed to this job, specifically when being run via resque as some additional
     * container-relevant args will be added to the job that are not relevant to the command.
     *
     * return array
     */
    abstract protected function getCommandArgs();


    /**
     * @return string
     */
    abstract protected function getIdentifier();


    public function run($args) {
        $command = $this->getCommand();
        $command->setContainer($this->getContainer());

        $input = new ArrayInput($this->getCommandArgs());
        $output = new StringOutput();

        $returnCode = ($this->isTestEnvironment()) ? $this->args['returnCode'] : $command->run($input, $output);

        if ($returnCode === 0) {
            return true;
        }

        return $this->handleNonZeroReturnCode($returnCode, $output);
    }

    protected function handleNonZeroReturnCode($returnCode, $output) {
        $this->getContainer()->get('logger')->error(get_class($this) . ': task [' . $this->getIdentifier() . '] returned ' . $returnCode);
        $this->getContainer()->get('logger')->error(get_class($this) . ': task [' . $this->getIdentifier() . '] output ' . trim($output->getBuffer()));

        return $returnCode;
    }


    /**
     * @return bool
     */
    private function isTestEnvironment() {
        if (!isset($this->args['kernel.environment'])) {
            return false;
        }

        return $this->args['kernel.environment'] == 'test';
    }
}