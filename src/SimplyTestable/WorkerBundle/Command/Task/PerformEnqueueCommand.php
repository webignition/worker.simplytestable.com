<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PerformEnqueueCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform:enqueue')
            ->setDescription('Enqueue task perform jobs for tasks waiting to be performed')
            ->setHelp('Enqueue task perform jobs for tasks waiting to be performed');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $queuedTaskIds = $this->getTaskService()->getEntityRepository()->getIdsByState(
            $this->getTaskService()->getQueuedState()
        );
        $output->writeln(count($queuedTaskIds).' queued tasks ready to be enqueued');

        foreach ($queuedTaskIds as $taskId) {
            if ($this->getResqueQueueService()->contains('task-perform', array('id' => $taskId))) {
                $output->writeln('Task ['.$taskId.'] is already enqueued');
            } else {
                $output->writeln('Enqueuing task ['.$taskId.']');

                $this->getResqueQueueService()->enqueue(
                    $this->getResqueJobFactoryService()->create(
                        'task-perform',
                        ['id' => $taskId]
                    )
                );
            }
        }

        return 0;
    }
}
