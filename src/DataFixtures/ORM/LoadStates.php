<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\State;

class LoadStates extends Fixture
{
    /**
     * @var string[]
     */
    private $stateNames = [
        'worker-active',
        'worker-awaiting-activation-verification',
        'worker-new',
        'task-cancelled',
        'task-completed',
        'task-in-progress',
        'task-queued',
        'taskoutput-sent',
        'taskoutput-sending',
        'taskoutput-queued',
        'task-failed-no-retry-available',
        'task-failed-retry-available',
        'task-failed-retry-limit-reached',
        'task-skipped',
        'worker-maintenance-read-only',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->stateNames as $stateName) {
            $stateRepository = $manager->getRepository(State::class);
            $state = $stateRepository->findOneBy([
                'name' => $stateName,
            ]);

            if (empty($state)) {
                $state = new State();
                $state->setName($stateName);

                $manager->persist($state);
                $manager->flush();
            }
        }
    }
}
