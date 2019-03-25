<?php

namespace App\Tests\Functional;

use App\Services\ApplicationState;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractBaseTestCase extends WebTestCase
{
    /**
     * @var Client|null
     */
    protected $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();

        self::$container->get(ApplicationState::class)->set(ApplicationState::STATE_ACTIVE);
    }

    protected function clearRedis()
    {
        $output = array();
        $returnValue = null;

        exec('redis-cli -r 1 flushall', $output, $returnValue);

        if ($output !== array('OK')) {
            return false;
        }

        return $returnValue === 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        \Mockery::close();

        $this->client = null;

        parent::tearDown();
    }
}
