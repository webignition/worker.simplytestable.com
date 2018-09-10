<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Task\Type\TaskTypeClass;
use App\Entity\Task\Type\Type as TaskType;

class LoadTaskTypes extends Fixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    private $taskTypes = [
        'HTML validation' => [
            'description' => 'Validates the HTML markup for a given URL',
            'class' => 'verification',
            'selectable' => true
        ],
        'CSS validation' => [
            'description' => 'Validates the CSS related to a given web document URL',
            'class' => 'verification',
            'selectable' => true
        ],
        'URL discovery' => [
            'description' => 'Discover in-scope URLs from the anchors within a given URL',
            'class' => 'discovery',
            'selectable' => false
        ],
        'Link integrity' => [
            'description' => 'Check links in a HTML document and determine those that don\'t work',
            'class' => 'verification',
            'selectable' => true
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $taskTypeClassRepository = $manager->getRepository(TaskTypeClass::class);
        $taskTypeRepository = $manager->getRepository(TaskType::class);

        foreach ($this->taskTypes as $name => $properties) {
            $taskType = $taskTypeRepository->findOneBy([
                'name' => $name,
            ]);

            if (is_null($taskType)) {
                $taskType = new TaskType();
            }

            $taskTypeClass = $taskTypeClassRepository->findOneBy([
                'name' => $properties['class'],
            ]);

            $taskType->setClass($taskTypeClass);
            $taskType->setDescription($properties['description']);
            $taskType->setName($name);
            $taskType->setSelectable($properties['selectable']);

            $manager->persist($taskType);
            $manager->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadTaskTypeClasses::class,
        ];
    }
}
