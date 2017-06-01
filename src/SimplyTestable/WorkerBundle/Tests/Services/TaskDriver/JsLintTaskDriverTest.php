<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\CssValidationTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskDriver\JsLintTaskDriver;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class JsLintTaskDriverTest extends BaseSimplyTestableTestCase
{
    /**
     * @inheritdoc
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        $client = parent::createClient($options, $server);

        $mockServices = [
            'simplytestable.services.nodeJslintWrapperService' => 'webignition\NodeJslint\Wrapper\Wrapper'
        ];

        foreach ($mockServices as $serviceId => $serviceClass) {
            $client->getContainer()->set($serviceId, \Mockery::mock($serviceClass));
        }

        return $client;
    }

    public function testFoo()
    {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200\nContent-Type:text/html\n\n<!DOCTYPE html>"
        )));

        $nodeJsLintWrapper = $this->container->get('simplytestable.services.nodeJslintWrapperService');
        $nodeJsLintWrapper->makePartial();

        $nodeJsLintOutput = \Mockery::mock(\webignition\NodeJslintOutput\NodeJslintOutput::class);

        $nodeJsLintWrapper
            ->shouldReceive('validate')
            ->andReturn($nodeJsLintOutput);

        /* @var $taskDriver JsLintTaskDriver */
        $taskDriver = $this->container->get('simplytestable.services.taskdriver.jslint');

        $taskData = $this->createTask('http://example.com', 'js static analysis', '');
        $task = $this->getTaskService()->getById($taskData->id);

        $taskDriver->perform($task);
    }
}
