<?php

namespace SimplyTestable\WorkerBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

class LoadTaskTypes extends AbstractFixture implements OrderedFixtureInterface
{    
    private $taskTypes = array(
        'HTML validation' => array(
            'description' => 'Validates the HTML markup for a given URL',
            'class' => 'verification',
            'selectable' => true
        ),
        'CSS validation' => array(
            'description' => 'Validates the CSS related to a given web document URL',
            'class' => 'verification',
            'selectable' => true
        ),
        'JS static analysis' => array(
            'description' => 'JavaScript static code analysis (via jslint)',
            'class' => 'verification',
            'selectable' => true
        ),
        'URL discovery' => array(
            'description' => 'Discover in-scope URLs from the anchors within a given URL',
            'class' => 'discovery',
            'selectable' => false
        ),
        'Link integrity' => array(
            'description' => 'Check links in a HTML document and determine those that don\'t work',
            'class' => 'verification',
            'selectable' => true
        ),
    );
    
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $taskTypeClassRepository = $manager->getRepository('SimplyTestable\WorkerBundle\Entity\Task\Type\TaskTypeClass');
        $taskTypeRepository = $manager->getRepository('SimplyTestable\WorkerBundle\Entity\Task\Type\Type');
        
        foreach ($this->taskTypes as $name => $properties) {
            $taskType = $taskTypeRepository->findOneByName($name);
            
            if (is_null($taskType)) {
                $taskType = new TaskType();
            }
            
            $taskTypeClass = $taskTypeClassRepository->findOneByName($properties['class']);
            
            $taskType->setClass($taskTypeClass);
            $taskType->setDescription($properties['description']);
            $taskType->setName($name);
            $taskType->setSelectable($properties['selectable']);
            
            $manager->persist($taskType);
            $manager->flush();            
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }
}