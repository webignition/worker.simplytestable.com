<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Maintenance;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class RequeueInProgressTasksCommandTest extends ConsoleCommandBaseTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->removeAllTasks();
        $this->clearRedis();
    }

    public function tearDown() {
        $this->clearRedis();
        parent::tearDown();
    }
    
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\WorkerBundle\Command\Maintenance\RequeueInProgressTasksCommand(),
        );
    }
    
    /**
     * @group standard
     */    
    public function testRequeueInProgressTasksCommand() {
        $urls = array(
            'http://example.com/zero/',
            'http://example.com/one/',
            'http://example.com/two/',
        );
        
        foreach ($urls as $index => $url) {            
            $task = $this->getTaskService()->getById($this->createTask($url, 'HTML validation')->id);
            
            if ($index > 0) {
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

        $this->clearRedis();

        $this->assertEquals(0, $this->executeCommand('simplytestable:maintenance:requeue-in-progress-tasks'));
        $this->assertEquals(3, count($this->getTaskService()->getEntityRepository()->getIdsByState($this->getTaskService()->getQueuedState())));        
    }


}
