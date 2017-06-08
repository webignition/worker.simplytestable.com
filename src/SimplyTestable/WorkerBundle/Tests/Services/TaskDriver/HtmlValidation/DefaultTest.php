<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation;

class DefaultTest extends TaskDriverTest {

    /**
     * @group standard
     */
    public function testProcessingValidatorResultsGetsCorrectErrorCount() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));

        $this->setHtmlValidatorFixture(
            file_get_contents($this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses/1'))
        );

        $task = $this->getDefaultTask();

        $this->assertEquals(0, $this->getTaskService()->perform($task));

        $this->assertEquals(3, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
    }

    /**
     * @group standard
     */
    public function testFailGracefullyWhenContentIsServedAsTextHtmlButIsNot() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));

        $task = $this->getDefaultTask();

        $this->assertEquals(0, $this->getTaskService()->perform($task));

        $outputObject = json_decode($task->getOutput()->getOutput());

        $this->assertEquals('document-is-not-markup', $outputObject->messages[0]->messageId);
    }


    /**
     * @group standard
     */
    public function testCharacterEncodingFailureSetsTaskStateAsFailed() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));

        $this->setHtmlValidatorFixture(
            file_get_contents($this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses/1'))
        );

        $task = $this->getDefaultTask();

        $this->assertEquals(0, $this->getTaskService()->perform($task));

        $this->assertEquals($this->getTaskService()->getFailedNoRetryAvailableState(), $task->getState());
    }


    /**
     * @group standard
     */
    public function testFailIncorrectWebResourceType() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));

        $task = $this->getDefaultTask();

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals($this->getTaskService()->getSkippedState(), $task->getState());
    }

    /**
     * @group standard
     */
    public function testBugfixRedmine392() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));

        $task = $this->getDefaultTask();

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals('{"messages":[{"message":"Internal Server Error","messageId":"http-retrieval-500","type":"error"}]}', $task->getOutput()->getOutput());
    }

}
