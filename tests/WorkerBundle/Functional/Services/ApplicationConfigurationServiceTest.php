<?php

namespace Tests\WorkerBundle\Functional\Services;

use SimplyTestable\WorkerBundle\Services\ApplicationConfigurationService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;

class ApplicationConfigurationServiceTest extends AbstractBaseTestCase
{
    public function testGet()
    {
        $projectRoot = realpath(__DIR__ . '/../../../..');
        $expectedRootDir = $projectRoot . '/app';
        $expectedWebDir = $projectRoot . '/web';

        $applicationConfigurationService = $this->container->get(ApplicationConfigurationService::class);

        $this->assertEquals($expectedRootDir, $applicationConfigurationService->getRootDir());
        $this->assertRegExp('/\/var\/cache\/test$/', $applicationConfigurationService->getCacheDir());
        $this->assertEquals($expectedWebDir, $applicationConfigurationService->getWebDir());
    }
}
