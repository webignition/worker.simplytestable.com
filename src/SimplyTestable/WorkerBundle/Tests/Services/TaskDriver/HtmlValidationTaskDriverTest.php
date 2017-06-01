<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\HtmlValidationTaskDriver;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class HtmlValidationTaskDriverTest extends BaseSimplyTestableTestCase
{
    /**
     * @inheritdoc
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        $client = parent::createClient($options, $server);

        $mockServices = [
            'simplytestable.services.htmlValidatorWrapperService' => 'webignition\HtmlValidator\Wrapper\Wrapper'
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

        $htmlValidatorWrapper = $this->container->get('simplytestable.services.htmlValidatorWrapperService');
        $htmlValidatorWrapper->makePartial();

        $htmlValidatorOutput = \Mockery::mock(\webignition\HtmlValidator\Output\Output::class);
        $htmlValidatorOutput
            ->shouldReceive('wasAborted')
            ->andReturn(false);
        $htmlValidatorOutput
            ->shouldReceive('getMessages')
            ->andReturn([]);
        $htmlValidatorOutput
            ->shouldReceive('getErrorCount')
            ->andReturn(0);

        $htmlValidatorWrapper
            ->shouldReceive('validate')
            ->andReturn($htmlValidatorOutput);

        /* @var $taskDriver HtmlValidationTaskDriver */
        $taskDriver = $this->container->get('simplytestable.services.taskdriver.htmlvalidation');

        $taskData = $this->createTask('http://example.com', 'html validation', '');
        $task = $this->getTaskService()->getById($taskData->id);

        $taskDriver->perform($task);
    }
}
