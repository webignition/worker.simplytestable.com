<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TaskReportCompletionCommand extends ContainerAwareCommand
{    
    /**
     *
     * @var string
     */
    //private $httpFixturePath;    
    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion')
            ->setDescription('Report back to the core application the completed status of a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to report')                
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
            ->setHelp(<<<EOF
Report back to the core application the completed status of a task
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
        
        if ($this->getTaskService()->reportCompletion($task) === false) {
            throw new \LogicException('Task execution failed, check log for details');
        }        
    } 
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TaskService
     */
    private function getTaskService() {
        return $this->getContainer()->get('simplytestable.services.taskservice');
    }
}