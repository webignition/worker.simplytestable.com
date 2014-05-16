<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation;

class WarningHasRefTest extends TaskDriverTest {

    /**
     * @var \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    private $task;

    
    public function setUp() {
        parent::setUp();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(). '/HttpResponses'));

        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath() . '/CssValidatorResponse/1')
        );

        $this->task = $this->getTask('http://www.example.com/', array(
            'vendor-extensions' => 'warn'
        ));

        $this->getTaskService()->perform($this->task);
    }


    public function testHasWarning() {
        $this->assertEquals(1, $this->task->getOutput()->getWarningCount());
    }

    public function testWarningHasRefProperty() {
        $this->assertEquals('http://example.com/style.css', json_decode($this->task->getOutput()->getOutput())[0]->ref);
    }

}
