<?php
namespace SimplyTestable\WorkerBundle\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class RequeueInProgressTasksCommand extends BaseCommand
{        
    const DEFAULT_AGE_IN_HOURS = 1;
    
    /**
     *
     * @var InputInterface
     */
    protected $input;     
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:requeue-in-progress-tasks')
            ->setDescription('Requeue tasks in progress that are started more than X hours ago')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'desc')
            ->addOption('age-in-hours', null, InputOption::VALUE_OPTIONAL, 'desc', self::DEFAULT_AGE_IN_HOURS)
            ->setHelp(<<<EOF
Requeue tasks in progress that are started prior to a given date
EOF
        );
    }    
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        
        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }
        
        $output->writeln('Using age-in-hours: <info>'.$this->getAgeInHours().'</info>');
        
        $startDateTime = new \DateTime('-'.$this->getAgeInHours($input).' hour');        
        $taskIds = $this->getTaskService()->getEntityRepository()->getUnfinishedIdsByMaxStartDate($startDateTime);
        
        $output->writeln('Tasks started more than '.$this->getAgeInHours().' hours ago: <info>'.count($taskIds).'</info>');
        $output->writeln(''); 
        
        $processedTaskCount = 0;
        
        foreach ($taskIds as $taskId) {
            $processedTaskCount++;
            $output->writeln('Processing task '.$taskId.' ('.$processedTaskCount.' of '.count($taskIds).')');
            
            $inProgressTask = $this->getTaskService()->getById($taskId);           
            $inProgressTask->setState($this->getTaskService()->getQueuedState());            
            
            if ($this->isDryRun()) {
                $this->getTaskService()->getEntityManager()->detach($inProgressTask);
            } else {
                $this->getTaskService()->getEntityManager()->persist($inProgressTask);                
                $this->getTaskService()->getEntityManager()->flush();

                $this->getResqueQueueService()->add(
                    'task-perform',
                    array(
                        'id' => $inProgressTask->getId()
                    )                
                );                
            }
            

        }
        
        $output->writeln('');        
    }
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TaskService
     */
    private function getTaskService() {
        return $this->getContainer()->get('simplytestable.services.taskservice');
    }     
    
    /**
     * 
     * @return boolean
     */
    private function isDryRun() {
        return $this->input->getOption('dry-run') !== false;
    }
    
    
    /**
     * 
     * @return int
     */
    private function getAgeInHours() {
        $age = $this->input->getOption('age-in-hours');
        if (!is_int($age) || $age < self::DEFAULT_AGE_IN_HOURS) {
            $age = self::DEFAULT_AGE_IN_HOURS;
        }
        
        return $age;
    }
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\ResqueQueueService
     */
    private function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resquequeueservice');
    }      
}