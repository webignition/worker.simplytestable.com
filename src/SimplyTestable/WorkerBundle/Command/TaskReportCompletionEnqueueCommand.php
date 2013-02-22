<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class TaskReportCompletionEnqueueCommand extends TaskCommand
{ 
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion:enqueue')
            ->setDescription('Enqueue task report completion jobs for tasks waiting to be report back completion state')
            ->setHelp(<<<EOF
Enqueue task report completion jobs for tasks waiting to be report back completion state
EOF
        );
    }    
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByState($this->getTaskService()->getCompletedState());
        $output->writeln(count($taskIds).' completed tasks ready to be enqueued');
        
        foreach ($taskIds as $taskId) {
            if ($this->getResqueQueueService()->contains('task-report-completion', array('id' => $taskId))) {
                $output->writeln('Task ['.$taskId.'] is already enqueued');
            } else {
                $output->writeln('Enqueuing task ['.$taskId.']');                
                $this->getResqueQueueService()->add(
                    'task-report-completion',
                    array(
                        'id' => $taskId
                    )                
                );                 
            }           
        }
               
        return 0;        
    }     
}