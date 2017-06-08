<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\RequestIfEmpty;

class IsEmptyTest extends RequestIfEmptyCommandTest {

    public function testResqueJobIsCreated() {
        $this->executeCommand('simplytestable:tasks:requestifempty');
        $this->assertTrue($this->getRequeQueueService()->contains('tasks-request'));
    }


    public function testResqueQueueLength() {
        $this->executeCommand('simplytestable:tasks:requestifempty');
        $this->assertEquals(1, $this->getRequeQueueService()->getQueueLength('tasks-request'));
    }
}