<?php

namespace App\Tests\Functional\Services;

use App\Services\ApplicationState;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\ObjectReflector;

class ApplicationStateTest extends AbstractBaseTestCase
{
    /**
     * @var ApplicationState
     */
    private $applicationState;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->applicationState = self::$container->get(ApplicationState::class);
    }

    public function testGetWithNoStateFile()
    {
        ObjectReflector::setProperty(
            $this->applicationState,
            ApplicationState::class,
            'state',
            null
        );

        $path = sprintf(
            '%s/config/state/%s',
            self::$container->getParameter('kernel.project_dir'),
            self::$container->getParameter('kernel.environment')
        );

        if (file_exists($path)) {
            unlink($path);
        }

        $this->assertEquals(ApplicationState::STATE_NEW, $this->applicationState->get());
    }

    /**
     * @dataProvider setGetDataProvider
     *
     * @param string $state
     * @param string $expectedState
     */
    public function testSetGet(string $state, string $expectedState)
    {
        $returnValue = $this->applicationState->set($state);

        $this->assertTrue($returnValue);
        $this->assertEquals($expectedState, $this->applicationState->get());

        $this->applicationState->set(ApplicationState::DEFAULT_STATE);
    }

    public function setGetDataProvider(): array
    {
        return [
            ApplicationState::STATE_ACTIVE => [
                'state' => ApplicationState::STATE_ACTIVE,
                'expectedState' => ApplicationState::STATE_ACTIVE,
            ],
            ApplicationState::STATE_NEW => [
                'state' => ApplicationState::STATE_NEW,
                'expectedState' => ApplicationState::STATE_NEW,
            ],
            ApplicationState::STATE_AWAITING_ACTIVATION_VERIFICATION => [
                'state' => ApplicationState::STATE_AWAITING_ACTIVATION_VERIFICATION,
                'expectedState' => ApplicationState::STATE_AWAITING_ACTIVATION_VERIFICATION,
            ],
        ];
    }

    public function testSetInvalidState()
    {
        $previousState = $this->applicationState->get();

        $returnValue = $this->applicationState->set('invalid');

        $this->assertFalse($returnValue);
        $this->assertEquals($previousState, $this->applicationState->get());
    }
}
