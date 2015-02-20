<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\BaseTest;
use SimplyTestable\WorkerBundle\Entity\Task\Task;

abstract class TaskDriverTest extends BaseTest {

    /**
     * @var Task
     */
    protected $task;

    public function setUp() {
        parent::setUp();

        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath('/HttpResponses')));

        $this->task = $this->getTask('http://example.com/', $this->getTaskParameters());
        $this->assertEquals(0, $this->getTaskService()->perform($this->task));
        $this->assertEquals($this->getExpectedErrorCount(), $this->task->getOutput()->getErrorCount());
    }

    abstract protected function getTaskParameters();
    abstract protected function getExpectedErrorCount();

    protected function getTaskTypeName() {
        return 'Link Integrity';
    }

}
