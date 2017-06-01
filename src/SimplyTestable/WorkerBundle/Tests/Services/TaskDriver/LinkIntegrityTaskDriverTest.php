<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\LinkIntegrityTaskDriver;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class LinkIntegrityTaskDriverTest extends BaseSimplyTestableTestCase
{
    public function testFoo()
    {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200\nContent-Type:text/html\n\n<!doctype html><html lang=en><head></head><body></body></html>"
        )));

        /* @var $taskDriver LinkIntegrityTaskDriver */
        $taskDriver = $this->container->get('simplytestable.services.taskdriver.linkintegrity');

        $taskData = $this->createTask('http://example.com', 'link integrity', '');
        $task = $this->getTaskService()->getById($taskData->id);

        $taskDriver->perform($task);
    }
}
