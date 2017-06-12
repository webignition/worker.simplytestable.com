<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\RequestIfEmpty;

class IsNotEmptyTest extends RequestIfEmptyCommandTest {

    public function testResqueJobIsNotCreated() {
        $this->getResqueQueueService()->enqueue(
            $this->getResqueJobFactoryService()->create(
                'tasks-request'
            )
        );

//        $this->getRequeQueueService()->enqueue(
//            $this->getResqueJobFactoryService()->create(
//                'tasks-request'
//            )
//        );

        //var_dump($this->getRequeQueueService()->getQueueLength('tasks-request'));
//
//

//
        $this->executeCommand('simplytestable:tasks:requestifempty');
        $this->assertEquals(1, $this->getResqueQueueService()->getQueueLength('tasks-request'));
    }
}
