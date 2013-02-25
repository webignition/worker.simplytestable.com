<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Services\CommandService;

class MaintenanceController extends BaseController
{   
    
    public function enableReadOnlyAction()
    {
        return $this->executeCommand('SimplyTestable\WorkerBundle\Command\MaintenanceEnableReadOnlyCommand');
    }    
    
    public function disableReadOnlyAction() {
        return $this->executeCommand('SimplyTestable\WorkerBundle\Command\MaintenanceDisableReadOnlyCommand');      
    }    
    
    public function taskPerformEnqueueAction() {
        return $this->executeCommand('SimplyTestable\WorkerBundle\Command\TaskPerformEnqueueCommand');         
    }
    
    public function leaveReadOnlyAction() {
        $MaintenanceDisableReadOnlyCommandResponse = $this->executeCommand('SimplyTestable\WorkerBundle\Command\MaintenanceDisableReadOnlyCommand');
        $TaskReportCompletionEnqueueCommandResponse = $this->executeCommand('SimplyTestable\WorkerBundle\Command\TaskReportCompletionEnqueueCommand');  
        $TaskPerformEnqueueCommandResponse = $this->executeCommand('SimplyTestable\WorkerBundle\Command\TaskPerformEnqueueCommand');
        
        $output = $MaintenanceDisableReadOnlyCommandResponse->getContent() . "\n" . $TaskReportCompletionEnqueueCommandResponse->getContent() . "\n" . $TaskPerformEnqueueCommandResponse->getContent();
        return $this->sendResponse($output);
    }
    
    
    private function executeCommand($commandClass, $inputArray = array()) {      
        $output = new \CoreSphere\ConsoleBundle\Output\StringOutput();
        $commandResponse =  $this->getCommandService()->execute(
                $commandClass,
                $inputArray,
                $output
        );
        
        $outputLines = explode("\n", trim($output->getBuffer()));
        
        return $this->sendResponse($outputLines, $commandResponse === 0 ? 200 : 500);        
    }

    
    /**
     *
     * @return CommandService
     */
    private function getCommandService() {
        return $this->container->get('simplytestable.services.commandService');
    }    
    
}
