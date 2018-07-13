<?php

namespace SimplyTestable\AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\AppBundle\Entity\State;

class LoadStates extends Fixture
{
    /**
     * @var array
     */
    private $stateDetails = [
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
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->stateDetails as $name => $nextStateName) {
            $stateRepository = $manager->getRepository(State::class);
            $state = $stateRepository->findOneBy([
                'name' => $name,
            ]);

            if (empty($state)) {
                $state = new State();
                $state->setName($name);

                if (!is_null($nextStateName)) {
                    $nextState = $stateRepository->findOneBy([
                        'name' => $nextStateName,
                    ]);

                    $state->setNextState($nextState);
                }

                $manager->persist($state);
                $manager->flush();
            }
        }
    }
}
