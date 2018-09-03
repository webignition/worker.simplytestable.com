<?php

namespace App\Tests\Functional\Services;

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
                    'url' => base64_encode('http://example.com/'),
                    'type' => 'html validation',
                    'parameter_hash' => 'foo',
                ],
                'expectedUrl' =>
                    'http://test.app.simplytestable.com/task/aHR0cDovL2V4YW1wbGUuY29tLw%3D%3D/'
                    .'html%20validation/foo/complete/',
            ],
        ];
    }
}
