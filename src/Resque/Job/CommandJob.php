<?php

namespace App\Resque\Job;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class CommandJob extends Job
{
    /**
     * @return Command
     */
    abstract protected function getCommand();

    /**
     * Get the arguments required by the to-be-run command.
     *
     * This may differ from the arguments passed to this job, specifically when being run via resque as some additional
     * container-relevant args will be added to the job that are not relevant to the command.
     *
     * @return array
     */
    abstract protected function getCommandArgs();

    /**
     * @return string
     */
    abstract protected function getIdentifier();

    /**
     * @param array $args
     *
     * @return int
     * @throws \Exception
     */
    public function run($args)
    {
        $command = $this->getCommand();
        $input = new ArrayInput($this->getCommandArgs());
        $output = new BufferedOutput();

        $returnCode = $command->run($input, $output);

        if ($returnCode === 0) {
            return 0;
        }

        return $this->handleNonZeroReturnCode($returnCode, $output);
    }

    /**
     * @param int $returnCode
     * @param BufferedOutput $output
     *
     * @return int
     */
    protected function handleNonZeroReturnCode($returnCode, $output)
    {
        $logger = $this->getContainer()->get('logger');

        $logger->error(sprintf(
            '%s: task [%s] returned %s',
            get_class($this),
            $this->getIdentifier(),
            $returnCode
        ));


        $logger->error(sprintf(
            '%s: task [%s] output %s',
            get_class($this),
            $this->getIdentifier(),
            trim($output->fetch())
        ));

        return $returnCode;
    }
}
