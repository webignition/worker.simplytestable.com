<?php

namespace SimplyTestable\WorkerBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\WorkerBundle\Services\StateService;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\WorkerBundle\Entity\State;

class LoadStates extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    private $stateDetails = array(
        'worker-active' => null,
        'worker-awaiting-activation-verification' => 'worker-active',
        'worker-new' => 'worker-awaiting-activation-verification',
        'task-cancelled' => null,
        'task-completed' => null,
        'task-in-progress' => 'task-completed',
        'task-queued' => 'task-in-progress',
        'taskoutput-sent' => null,
        'taskoutput-sending' => 'taskoutput-sent',
        'taskoutput-queued' => 'taskoutput-sending',
        'task-failed-no-retry-available' => null,
        'task-failed-retry-available' => null,
        'task-failed-retry-limit-reached' => null,
        'task-skipped' => null,
        'worker-maintenance-read-only' => null
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->stateDetails as $name => $nextStateName) {
            if (!$this->getStateService()->has($name)) {
                $state = new State();
                $state->setName($name);

                if (!is_null($nextStateName)) {
                    $state->setNextState($this->getStateService()->find($nextStateName));
                }

                $manager->persist($state);
                $manager->flush();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }

    /**
     * @return StateService
     */
    public function getStateService()
    {
        return $this->container->get('simplytestable.services.stateservice');
    }
}
