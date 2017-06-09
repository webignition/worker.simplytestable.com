<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\CommandService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PerformAllCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform:all')
            ->setDescription('Perform all jobs queued')
            ->addOption('dry-run')
            ->setHelp('Perform all jobs queued');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $queuedTaskIds = $this->getTaskService()->getEntityRepository()->getIdsByState(
            $this->getTaskService()->getQueuedState()
        );
        $output->writeln(count($queuedTaskIds).' queued tasks ready to be performed');

        foreach ($queuedTaskIds as $taskId) {
            $output->writeln('Issuing perform command for task '.$taskId);

            $outputBuffer = new StringOutput();

            if ($this->isDryRun($input)) {
                $commandResponse = 'dry run';
            } else {
                $commandResponse =  $this->getCommandService()->execute(
                    'SimplyTestable\WorkerBundle\Command\Task\PerformCommand',
                    array(
                        'id' => $taskId
                    ),
                    $outputBuffer
                );
            }

            $output->writeln(trim($outputBuffer->getBuffer()));
            $output->writeln('Command completed with return code '.$commandResponse);
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     *
     * @return bool
     */
    private function isDryRun(InputInterface $input)
    {
        return $input->getOption('dry-run') !== false;
    }

    /**
     *
     * @return CommandService
     */
    private function getCommandService()
    {
        return $this->getContainer()->get('simplytestable.services.commandService');
    }
}
