<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class PerformEnqueueCommand extends Command
{ 
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform:enqueue')
            ->setDescription('Enqueue task perform jobs for tasks waiting to be performed')
            ->setHelp(<<<EOF
Enqueue task perform jobs for tasks waiting to be performed
EOF
        );
    }    
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $queuedTaskIds = $this->getTaskService()->getEntityRepository()->getIdsByState($this->getTaskService()->getQueuedState());
        $output->writeln(count($queuedTaskIds).' queued tasks ready to be enqueued');
        
        foreach ($queuedTaskIds as $taskId) {
            if ($this->getResqueQueueService()->contains('task-perform', array('id' => $taskId))) {
                $output->writeln('Task ['.$taskId.'] is already enqueued');
            } else {
                $output->writeln('Enqueuing task ['.$taskId.']');                
                $this->getResqueQueueService()->add(
                    'task-perform',
                    array(
                        'id' => $taskId
                    )                
                );                 
            }           
        }
        
        return 0;        
    }     
}