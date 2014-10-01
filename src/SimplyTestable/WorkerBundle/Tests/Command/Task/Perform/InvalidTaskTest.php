<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

class InvalidTaskTest extends PerformCommandTest {

    /**
     * @var int
     */
    private $commandReturnCode;

    public function setUp() {
        parent::setUp();

        $this->commandReturnCode = $this->executeCommand('simplytestable:task:perform', array(
            'id' => 1
        ));
    }


    /**
     * @group standard
     */
    public function testReturnsStatusCode() {
        $this->assertEquals(-2, $this->commandReturnCode);
    }


}
