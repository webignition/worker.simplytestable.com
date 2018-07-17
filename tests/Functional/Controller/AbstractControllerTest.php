<?php

namespace App\Tests\Functional\Controller;

use Symfony\Component\Routing\RouterInterface;
use App\Tests\Functional\AbstractBaseTestCase;

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
