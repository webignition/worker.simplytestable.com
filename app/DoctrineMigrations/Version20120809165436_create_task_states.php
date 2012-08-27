<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\WorkerBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120809165436_create_task_states extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {
        $state_cancelled = new State();
        $state_cancelled->setName('task-cancelled');        
        $this->getEntityManager()->persist($state_cancelled);
        $this->getEntityManager()->flush();        
        
        $state_completed = new State();
        $state_completed->setName('task-completed');        
        $this->getEntityManager()->persist($state_completed);
        $this->getEntityManager()->flush();
        
        $state_in_progress = new State();
        $state_in_progress->setName('task-in-progress');
        $state_in_progress->setNextState($state_completed);        
        $this->getEntityManager()->persist($state_in_progress);
        $this->getEntityManager()->flush();        
        
        $state_queued = new State();
        $state_queued->setName('task-queued');
        $state_queued->setNextState($state_in_progress);        
        $this->getEntityManager()->persist($state_queued);
        $this->getEntityManager()->flush();      
    }

    public function postDown(Schema $schema)
    {
        $stateNames = array(
            'task-queued',
            'task-in-progress',
            'task-completed',
            'task-cancelled'            
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\WorkerBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();
        }
    }
}
