<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
 SimplyTestable\WorkerBundle\Entity\Task\Type\Type,
 SimplyTestable\WorkerBundle\Entity\Task\Type\TaskTypeClass,
 Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130108162655_add_Url_Discovery_TaskType extends EntityModificationMigration
{
    private $taskTypes = array(
        'URL discovery' => array(
            'description' => 'Discover URLs relevant for testing in the content of a given URL',
            'class' => 'discovery'
        )
    );
    
    public function postUp(Schema $schema)
    {
        foreach ($this->taskTypes as $name => $properties) {
            $class = $taskTypeClass = $this->getEntityManager()->getRepository('SimplyTestable\WorkerBundle\Entity\Task\Type\TaskTypeClass')->findOneByName($properties['class']);
 
            $taskType = new Type();
            $taskType->setClass($class);
            $taskType->setDescription($properties['description']);
            $taskType->setName($name);
            
            $this->getEntityManager()->persist($taskType);
            $this->getEntityManager()->flush();            
        }
    }
    
    public function postDown(Schema $schema)
    {
        foreach ($this->taskTypes as $name => $properties) {            
            $taskType = $this->getEntityManager()->getRepository('SimplyTestable\WorkerBundle\Entity\Task\Type\Type')->findOneByName($properties[$name]);
            $this->getEntityManager()->remove($taskType);
            $this->getEntityManager()->flush();
        }        
    }
}
