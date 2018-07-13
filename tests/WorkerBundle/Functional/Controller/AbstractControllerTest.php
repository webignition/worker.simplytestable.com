<?php

namespace Tests\WorkerBundle\Functional\Controller;

use Symfony\Component\Routing\RouterInterface;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;

abstract class AbstractControllerTest extends AbstractBaseTestCase
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->router = self::$container->get(RouterInterface::class);
    }
}
