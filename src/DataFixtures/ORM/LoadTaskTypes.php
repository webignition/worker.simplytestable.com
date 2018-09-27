<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Task\Type as TaskType;

class LoadTaskTypes extends Fixture
{
    /**
     * @var array
     */
    private $taskTypes = [
        'HTML validation' => [
            'description' => 'Validates the HTML markup for a given URL',
            'selectable' => true
        ],
        'CSS validation' => [
            'description' => 'Validates the CSS related to a given web document URL',
            'selectable' => true
        ],
        'URL discovery' => [
            'description' => 'Discover in-scope URLs from the anchors within a given URL',
            'selectable' => false
        ],
        'Link integrity' => [
            'description' => 'Check links in a HTML document and determine those that don\'t work',
            'selectable' => true
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $taskTypeRepository = $manager->getRepository(TaskType::class);

        foreach ($this->taskTypes as $name => $properties) {
            $taskType = $taskTypeRepository->findOneBy([
                'name' => $name,
            ]);

            if (is_null($taskType)) {
                $taskType = new TaskType();
            }

            $taskType->setDescription($properties['description']);
            $taskType->setName($name);
            $taskType->setSelectable($properties['selectable']);

            $manager->persist($taskType);
            $manager->flush();
        }
    }
}
