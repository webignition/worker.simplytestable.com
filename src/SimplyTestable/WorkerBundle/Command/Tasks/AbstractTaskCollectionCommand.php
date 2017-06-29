<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use SimplyTestable\WorkerBundle\Output\StringOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

abstract class AbstractTaskCollectionCommand extends Command
{
    /**
     * @param int[] $taskIds
     * @param Command $command
     * @param OutputInterface $output
     */
    public function executeForCollection($taskIds, Command $command, OutputInterface $output)
    {
        foreach ($taskIds as $taskId) {
            $output->writeln('Issuing command for task '.$taskId);

            $outputBuffer = new StringOutput();

            $commandResponse = $command->run(
                new ArrayInput([
                    'id' => $taskId
                ]),
                $outputBuffer
            );

            $output->writeln(trim($outputBuffer->getBuffer()));
            $output->writeln('Completed with return code '.$commandResponse);
        }
    }
}
