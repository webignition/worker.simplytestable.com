<?php

namespace App\Tests\Functional\Services;

use App\Services\ApplicationConfiguration;
use App\Tests\Functional\AbstractBaseTestCase;

class ApplicationConfigurationTest extends AbstractBaseTestCase
{
    /**
     * @var ApplicationConfiguration
     */
    private $applicationConfiguration;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->applicationConfiguration = self::$container->get(ApplicationConfiguration::class);
    }

    public function testGetHostname()
    {
        $this->assertSame(self::$container->getParameter('hostname'), $this->applicationConfiguration->getHostname());
    }

    public function testGetToken()
    {
        $this->assertSame(self::$container->getParameter('token'), $this->applicationConfiguration->getToken());
    }
}
