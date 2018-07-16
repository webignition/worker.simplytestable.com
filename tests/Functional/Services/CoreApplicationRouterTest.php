<?php

namespace App\Tests\Functional\Guzzle;

use App\Services\CoreApplicationRouter;
use App\Tests\Functional\AbstractBaseTestCase;

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
        $coreApplicationRouter = self::$container->get(CoreApplicationRouter::class);
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
                'expectedUrl' => 'http://test.app.simplytestable.com/worker/activate/',
            ],
            'tasks_request' => [
                'name' => 'tasks_request',
                'parameters' => [],
                'expectedUrl' => 'http://test.app.simplytestable.com/worker/tasks/request/',
            ],
            'task_complete' => [
                'name' => 'task_complete',
                'parameters' => [
                    'url' => 'http://example.com/',
                    'type' => 'html validation',
                    'parameter_hash' => 'foo',
                ],
                'expectedUrl' =>
                    'http://test.app.simplytestable.com/task/http%3A%2F%2Fexample.com%2F/html%20validation/foo/complete/',
            ],
        ];
    }
}
