<?php

namespace Tests\WorkerBundle\Functional\Services;

use SimplyTestable\WorkerBundle\Services\ResourceLocator;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class ResourceLocatorTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider locateDataProvider
     *
     * @param string $name
     * @param string $expectedRelativeResourcePath
     */
    public function testLocate($name, $expectedRelativeResourcePath)
    {
        $resourceLocator = $this->container->get(ResourceLocator::class);

        $expectedAbsoluteResourcePath = str_replace(
            '/app',
            '/',
            $this->container->get('kernel')->getRootDir() . $expectedRelativeResourcePath
        );

        $this->assertEquals($expectedAbsoluteResourcePath, $resourceLocator->locate($name));
    }

    /**
     * @return array
     */
    public function locateDataProvider()
    {
        return [
            'core application routes' => [
                'name' => '@SimplyTestableWorkerBundle/Resources/config/coreapplicationrouting.yml',
                'expectedRelativeResourcePath' =>
                    'src/SimplyTestable/WorkerBundle/Resources/config/coreapplicationrouting.yml',
            ],
        ];
    }
}
