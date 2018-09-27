<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Task\Type\TaskTypeClass;

class LoadTaskTypeClasses extends Fixture
{
    /**
     * @var array
     */
    private $taskTypeClasses = [
        'verification' => 'For the verification of quality aspects such as the presence of a robots.txt file',
        'discovery' => 'For the discovery of information, such as collecting all unique URLs within a given page',
        'validation' => 'For the validation of syntactial correctness, such as HTML or CSS validation'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(TaskTypeClass::class);

        foreach ($this->taskTypeClasses as $name => $description) {
            $existingTaskTypeClass = $repository->findOneBy([
                'name' => $name,
            ]);

            if (empty($existingTaskTypeClass)) {
                $taskTypeClass = new TaskTypeClass();
                $taskTypeClass->setName($name);
                $taskTypeClass->setDescription($description);

                $manager->persist($taskTypeClass);
                $manager->flush();
            }
        }
    }
}
