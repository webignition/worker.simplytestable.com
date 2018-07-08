<?php

namespace Tests\WorkerBundle\Functional\Guzzle;

use SimplyTestable\WorkerBundle\Services\CoreApplicationRouter;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;

class CoreApplicationRouterTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider generateDataProvider
     *
     * @param string $name
     * @param array $parameters
     * @param string $expectedUrl
     */
    public function testGenerate($name, $parameters, $expectedUrl)
    {
        $coreApplicationRouter = $this->container->get(CoreApplicationRouter::class);
        $this->assertEquals($expectedUrl, $coreApplicationRouter->generate($name, $parameters));
    }

    /**
     * @return array
     */
    public function generateDataProvider()
    {
        return [
            'worker_activate' => [
                'name' => 'worker_activate',
                'parameters' => [],
                'expectedUrl' => 'http://app.simplytestable.com/worker/activate/',
            ],
            'tasks_request' => [
                'name' => 'tasks_request',
                'parameters' => [],
                'expectedUrl' => 'http://app.simplytestable.com/worker/tasks/request/',
            ],
            'task_complete' => [
                'name' => 'task_complete',
                'parameters' => [
                    'url' => 'http://example.com/',
                    'type' => 'html validation',
                    'parameter_hash' => 'foo',
                ],
                'expectedUrl' =>
                    'http://app.simplytestable.com/task/http%3A%2F%2Fexample.com%2F/html%20validation/foo/complete/',
            ],
        ];
    }
}
