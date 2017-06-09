<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\CommandService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCompletionAllCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion:all')
            ->setDescription('Report completion for all jobs finished')
            ->addOption('dry-run')
            ->setHelp('Report completion for all jobs finished');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $taskIdsWithOutput = $this->getTaskService()->getEntityRepository()->getIdsWithOutput();
        $output->writeln(count($taskIdsWithOutput).' tasks with output ready to report completion');

        foreach ($taskIdsWithOutput as $taskId) {
            $output->writeln('Issuing report completion command for task '.$taskId);

            $outputBuffer = new StringOutput();

            if ($this->isDryRun($input)) {
                $commandResponse = 'dry run';
            } else {
                $commandResponse =  $this->getCommandService()->execute(
                        'SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand',
                        array('id' => $taskId),
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
     * @return CommandService
     */
    private function getCommandService()
    {
        return $this->getContainer()->get('simplytestable.services.commandService');
    }
}
