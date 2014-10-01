<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success;

use SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\RequestCommandTest;

abstract class SuccessTest extends RequestCommandTest {

    /**
     * @var int
     */
    private $returnCode;

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

    /**
     * @return int
     */
    private function getRequiredCurrentTaskCount() {
        $classNameParts = explode('\\', get_class($this));
        return (int)str_replace(['Task', 'Test'], '', $classNameParts[count($classNameParts) - 1]);
    }

    /**
     * @return int
     */
    private function getExpectedRequestedTaskCount() {
        return $this->getTasksService()->getTaskRequestLimit() - $this->getRequiredCurrentTaskCount();
    }

    public function testReturnStatusCode() {
        $this->assertEquals(0, $this->returnCode);
    }

    public function testResqueJobToQueueIsEmpty() {
        $this->assertFalse($this->getRequeQueueService()->contains('tasks-request'));
    }

    public function testRequestedTaskCount() {
        $this->assertTrue(substr_count($this->getHttpClientService()->getHistory()->getLastRequest()->getUrl(), '&limit=' . $this->getExpectedRequestedTaskCount()) > 0);
    }
}
