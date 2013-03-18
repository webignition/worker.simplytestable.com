<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Maintenance;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class RequeueInProgressTasksCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }     

    public function testEnableReadOnlyModeCorrectlyChangesState() {
        $urls = array(
            'http://example.com/zero/',
            'http://example.com/one/',
            'http://example.com/two/',
        );
        
        $taskObjects = array();
        
        foreach ($urls as $url) {            
            $task = $this->getTaskService()->getById($this->createTask($url, 'HTML validation')->id);
            
            if ($task->getId() > 1) {
                $task->setState($this->getTaskService()->getInProgressState());

                $timePeriod = new TimePeriod();
                $timePeriod->setStartDateTime(new \DateTime('-25 hour'));

                $task->setTimePeriod($timePeriod);

                $this->getTaskService()->getEntityManager()->persist($task);                
            }
            

        }
        
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertEquals(1, count($this->getTaskService()->getEntityRepository()->getIdsByState($this->getTaskService()->getQueuedState())));
        $this->assertEquals(2, count($this->getTaskService()->getEntityRepository()->getIdsByState($this->getTaskService()->getInProgressState())));
        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:requeue-in-progress-tasks'));
        
        $this->assertEquals(3, count($this->getTaskService()->getEntityRepository()->getIdsByState($this->getTaskService()->getQueuedState())));        
    }


}
