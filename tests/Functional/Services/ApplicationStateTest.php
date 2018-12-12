<?php

namespace App\Tests\Functional\Services;

use App\Services\ApplicationState;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\ObjectPropertySetter;

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
        ObjectPropertySetter::setProperty(
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
    public function testSetGet($state, $expectedState)
    {
        $returnValue = $this->applicationState->set($state);

        $this->assertTrue($returnValue);
        $this->assertEquals($expectedState, $this->applicationState->get());

        $this->applicationState->set(ApplicationState::DEFAULT_STATE);
    }

    /**
     * @return array
     */
    public function setGetDataProvider()
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

    /**
     * @dataProvider isInStateDataProvider
     *
     * @param string $state
     * @param bool $expectedIsActive
     * @param bool $expectedIsAwaitingActivationVerification
     * @param bool $expectedIsNew
     */
    public function testIsInState(
        $state,
        $expectedIsActive,
        $expectedIsAwaitingActivationVerification,
        $expectedIsNew
    ) {
        $this->applicationState->set($state);

        $this->assertEquals(
            $expectedIsActive,
            $this->applicationState->isActive()
        );

        $this->assertEquals(
            $expectedIsAwaitingActivationVerification,
            $this->applicationState->isAwaitingActivationVerification()
        );

        $this->assertEquals(
            $expectedIsNew,
            $this->applicationState->isNew()
        );

        $this->applicationState->set(ApplicationState::DEFAULT_STATE);
    }

    /**
     * @return array
     */
    public function isInStateDataProvider()
    {
        return [
            ApplicationState::STATE_ACTIVE => [
                'state' => ApplicationState::STATE_ACTIVE,
                'expectedIsActive' => true,
                'expectedIsAwaitingActivationVerification' => false,
                'expectedIsNew' => false,
            ],
            ApplicationState::STATE_AWAITING_ACTIVATION_VERIFICATION => [
                'state' => ApplicationState::STATE_AWAITING_ACTIVATION_VERIFICATION,
                'expectedIsInActiveState' => false,
                'expectedIsAwaitingActivationVerification' => true,
                'expectedIsNew' => false,
            ],
            ApplicationState::STATE_NEW => [
                'state' => ApplicationState::STATE_NEW,
                'expectedIsInActiveState' => false,
                'expectedIsAwaitingActivationVerification' => false,
                'expectedIsNew' => true,
            ],
        ];
    }
}