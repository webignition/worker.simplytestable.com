<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

class SuccessTest extends PerformCommandTest {

    /**
     * @var int
     */
    private $commandReturnCode;

    /**
     * @var int
     */
    private $taskId;


    public function setUp() {
        parent::setUp();

        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath('/HtmlValidatorResponses')
        );

        $task = $this->createTask('http://example.com/', 'HTML validation');
        $this->taskId = $task->id;

        $this->clearRedis();

        $this->commandReturnCode = $this->executeCommand('simplytestable:task:perform', array(
            'id' => $this->taskId
        ));
    }


    /**
     * @group standard
     */
    public function testReturnsStatusCode() {
        $this->assertEquals(0, $this->commandReturnCode);
    }


    public function testResqueTaskReportCompletionJobIsCreated() {
        $this->assertTrue($this->getRequeQueueService()->contains(
            'task-report-completion', ['id' => $this->taskId]
        ));
    }


    public function testResqueTasksRequestJobIsCreated() {
        $this->assertTrue($this->getRequeQueueService()->contains(
            'tasks-request'
        ));
    }


}
