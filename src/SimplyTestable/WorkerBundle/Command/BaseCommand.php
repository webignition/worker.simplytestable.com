<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class BaseCommand extends ContainerAwareCommand
{    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\WorkerService
     */
    protected function getWorkerService() {
        return $this->getContainer()->get('simplytestable.services.workerservice');
    } 
    
    /**
     * 
     * @param int $number
     * @return boolean
     */
    protected function isHttpStatusCode($number) {
        return strlen($number) == 3;
    }      
}