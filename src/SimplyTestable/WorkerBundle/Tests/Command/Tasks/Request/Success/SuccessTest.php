<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success;

use SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\RequestCommandTest;

abstract class SuccessTest extends RequestCommandTest {

    /**
     * @var int
     */
    protected $returnCode;

    public function setUp() {
        parent::setUp();

        $this->removeAllTasks();

        for ($index = 0; $index < $this->getRequiredCurrentTaskCount(); $index++) {
            $this->createTask('http://foo.example.com/' . $index, 'HTML Validation');
        }

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 200'
        ]));

        $this->clearRedis();

        $this->returnCode = $this->executeCommand('simplytestable:tasks:request');
    }

    abstract protected function getExpectedReturnStatusCode();
    abstract protected function getExpectedResqueQueueIsEmpty();

    /**
     * @return int
     */
    protected function getRequiredCurrentTaskCount() {
        $classNameParts = explode('\\', get_class($this));
        return (int)str_replace(['Task', 'Test'], '', $classNameParts[count($classNameParts) - 1]);
    }

    public function testReturnStatusCode() {
        $this->assertEquals($this->getExpectedReturnStatusCode(), $this->returnCode);
    }

    public function testResqueJobToQueueIsEmpty() {
        $this->assertEquals($this->getExpectedResqueQueueIsEmpty(), $this->getRequeQueueService()->isEmpty('tasks-request'));
    }
}
