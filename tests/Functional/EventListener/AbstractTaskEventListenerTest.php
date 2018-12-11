<?php

namespace App\Tests\Functional\EventListener;

use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractTaskEventListenerTest extends AbstractBaseTestCase
{
    const TASK_ID = 1;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->eventDispatcher = self::$container->get(EventDispatcherInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());

        parent::tearDown();
        \Mockery::close();
    }
}
