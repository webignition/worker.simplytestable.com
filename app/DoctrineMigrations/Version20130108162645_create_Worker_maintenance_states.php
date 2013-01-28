<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    SimplyTestable\WorkerBundle\Entity\State,
    SimplyTestable\WorkerBundle\Entity\ThisWorker,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130108162645_create_Worker_maintenance_states extends EntityModificationMigration
{   
    
    private $stateNames = array(
        'worker-maintenance-read-only'
    );
    
    public function postUp(Schema $schema)
    {
        foreach ($this->stateNames as $stateName) {
            $state = new State();
            $state->setName($stateName);
            $this->getEntityManager()->persist($state);
            $this->getEntityManager()->flush();            
        }
    }  
    
    
    public function postDown(Schema $schema)
    {
        foreach ($this->stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\WorkerBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();        
        }     
    }    

}
