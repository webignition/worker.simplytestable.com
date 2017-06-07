<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

abstract class BaseTest extends BaseSimplyTestableTestCase {

    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }

    public function setUp() {
        parent::setUp();
        $this->clearMemcacheHttpCache();
    }


    /**
     * @return string
     */
    abstract protected function getTaskTypeName();

    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    protected function getDefaultTask() {
        return $this->getTask('http://example.com/');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    protected function getTask($url, $parameters = array()) {
        return $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'url' => $url,
            'type' => $this->getTaskTypeName(),
            'parameters' => json_encode($parameters),
        ]));
    }

}
