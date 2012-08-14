<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TaskPerformCommand extends ContainerAwareCommand
{    
    /**
     *
     * @var string
     */
    //private $httpFixturePath;    
    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform')
            ->setDescription('Start a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to start')                
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
            ->setHelp(<<<EOF
Start a task
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        if ($input->hasArgument('http-fixture-path')) {
            $httpClient = $this->getContainer()->get('simplytestable.services.httpClient');
            
            if ($httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $httpClient->getStoredResponseList()->setFixturesPath($input->getArgument('http-fixture-path'));
            }            
        }
        
        $task = $this->getTaskService()->getById($input->getArgument('id'));                
        
        if ($this->getTaskService()->perform($task) === false) {
            throw new \LogicException('Task execution failed, check log for details');
        }
        
        $this->getContainer()->get('simplytestable.services.resqueQueueService')->add(
            'SimplyTestable\WorkerBundle\Resque\Job\TaskReportCompletionJob',
            'task-report-completion',
            array(
                'id' => $task->getId()
            )                
        );        
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
     * @return \SimplyTestable\WorkerBundle\Services\TaskDriver\FactoryService
     */
    private function getTaskDriverFactoryService() {
        return $this->getContainer()->get('simplytestable.services.taskdriverfactoryservice');
    }
}