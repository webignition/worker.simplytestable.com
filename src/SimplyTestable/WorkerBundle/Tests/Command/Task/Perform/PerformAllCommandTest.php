<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformAllCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }

    
    /**
     * @group standard
     */    
    public function testPerformAll() {        
        $taskProperties = $this->createTask('http://example.com/', 'HTML validation');        
        
        $task = $this->getTaskService()->getById($taskProperties->id);
        $task->setState($this->getTaskService()->getQueuedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );
        
        $response = $this->runConsole('simplytestable:task:perform:all');          
        
        $this->assertEquals(0, $response);
    } 
    
    
   


}
