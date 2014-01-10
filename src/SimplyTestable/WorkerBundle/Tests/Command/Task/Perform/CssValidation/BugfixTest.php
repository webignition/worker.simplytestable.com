<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\CssValidation;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class BugfixTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'CSS validation';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
    

    /**
     * @group standard
     */        
    public function testTest() {    
//        // http://worker.simplytestable.com/app_dev.php/task/create/?url=http://shkspr.mobi/blog/2014/01/plants-vs.-zombies%E2%84%A2-2-vs.-plants-vs.-zombies%E2%84%A2-2/&type=html%20validation
//        
//        $this->clearMemcacheHttpCache();  
//        //$this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
//        
////        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
////            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
////        );        
//        
//        $this->container->get('simplytestable.services.cssValidatorWrapperService')->enableDeferToParentIfNoRawOutput();         
//        
//        $taskObject = $this->createTask('http://shkspr.mobi/blog/2014/01/plants-vs.-zombies%E2%84%A2-2-vs.-plants-vs.-zombies%E2%84%A2-2/', $this->getTaskTypeName());         
//     
//        $task = $this->getTaskService()->getById($taskObject->id);
//        
//        $response = $this->runConsole('simplytestable:task:perform', array(
//            $task->getId() => true
//        ));
//        
//        $this->assertEquals(0, $response); 
//        
//        var_dump($task->getOutput()->getErrorCount()); 
    } 
}
