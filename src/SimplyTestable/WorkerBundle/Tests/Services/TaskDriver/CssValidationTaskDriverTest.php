<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\CssValidationTaskDriver;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class CssValidationTaskDriverTest extends BaseSimplyTestableTestCase
{
    /**
     * @inheritdoc
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        $client = parent::createClient($options, $server);

        $mockServices = [
            'simplytestable.services.cssValidatorWrapperService' => 'webignition\CssValidatorWrapper\Wrapper'
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

        $cssValidatorWrapper = $this->container->get('simplytestable.services.cssValidatorWrapperService');
        $cssValidatorWrapper->makePartial();

        $cssValidatorOutput = \Mockery::mock(\webignition\CssValidatorOutput\CssValidatorOutput::class);
        $cssValidatorOutput
            ->shouldReceive('hasException')
            ->andReturn(false);
        $cssValidatorOutput
            ->shouldReceive('getErrorCount')
            ->andReturn(0);
        $cssValidatorOutput
            ->shouldReceive('getWarningCount')
            ->andReturn(0);
        $cssValidatorOutput
            ->shouldReceive('getMessages')
            ->andReturn([]);

        $cssValidatorWrapper
            ->shouldReceive('validate')
            ->andReturn($cssValidatorOutput);

        /* @var $taskDriver CssValidationTaskDriver */
        $taskDriver = $this->container->get('simplytestable.services.taskdriver.cssvalidation');

        $taskData = $this->createTask('http://example.com', 'css validation', '');
        $task = $this->getTaskService()->getById($taskData->id);

        $taskDriver->perform($task);
    }
}
