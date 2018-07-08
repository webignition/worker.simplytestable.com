<?php

namespace Tests\WorkerBundle\Functional;

use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractBaseTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->client = static::createClient();

        self::$container->get(WorkerService::class)->setActive();
        self::$container->get('doctrine')->getConnection()->beginTransaction();
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
        self::$container->get('doctrine')->getConnection()->close();

        \Mockery::close();
        parent::tearDown();
    }
}
