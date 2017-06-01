<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\UrlDiscoveryTaskDriver;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class UrlDiscoveryTaskDriverTest extends BaseSimplyTestableTestCase
{
//    /**
//     * @inheritdoc
//     */
//    protected static function createClient(array $options = array(), array $server = array())
//    {
//        $client = parent::createClient($options, $server);
//
//        $mockServices = [
//            'simplytestable.services.htmlValidatorWrapperService' => 'webignition\HtmlValidator\Wrapper\Wrapper'
//        ];
//
//        foreach ($mockServices as $serviceId => $serviceClass) {
//            $client->getContainer()->set($serviceId, \Mockery::mock($serviceClass));
//        }
//
//        return $client;
//    }

    public function testFoo()
    {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200\nContent-Type:text/html\n\n<!doctype html><html lang=en><head></head><body></body></html>"
        )));

//        $htmlValidatorWrapper = $this->container->get('simplytestable.services.htmlValidatorWrapperService');
//        $htmlValidatorWrapper->makePartial();
//
//        $htmlValidatorOutput = \Mockery::mock(\webignition\HtmlValidator\Output\Output::class);
//        $htmlValidatorOutput
//            ->shouldReceive('wasAborted')
//            ->andReturn(false);
//        $htmlValidatorOutput
//            ->shouldReceive('getMessages')
//            ->andReturn([]);
//        $htmlValidatorOutput
//            ->shouldReceive('getErrorCount')
//            ->andReturn(0);
//
//        $htmlValidatorWrapper
//            ->shouldReceive('validate')
//            ->andReturn($htmlValidatorOutput);

        /* @var $taskDriver UrlDiscoveryTaskDriver */
        $taskDriver = $this->container->get('simplytestable.services.taskdriver.urldiscovery');

        $taskData = $this->createTask('http://example.com', 'url discovery', '');
        $task = $this->getTaskService()->getById($taskData->id);

        $taskDriver->perform($task);
    }
}
