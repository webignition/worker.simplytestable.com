<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation;

class DefaultTest extends TaskDriverTest {

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

}
