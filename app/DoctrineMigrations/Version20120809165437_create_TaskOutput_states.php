<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\WorkerBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120809165437_create_TaskOutput_states extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {
        $state_sent = new State();
        $state_sent->setName('taskoutput-sent');        
        $this->getEntityManager()->persist($state_sent);
        $this->getEntityManager()->flush();
        
        $state_sending = new State();
        $state_sending->setName('taskoutput-sending');
        $state_sending->setNextState($state_sent);        
        $this->getEntityManager()->persist($state_sending);
        $this->getEntityManager()->flush();        
        
        $state_queued = new State();
        $state_queued->setName('taskoutput-queued');
        $state_queued->setNextState($state_sending);        
        $this->getEntityManager()->persist($state_queued);
        $this->getEntityManager()->flush();      
    }

    public function postDown(Schema $schema)
    {
        $stateNames = array(
            'taskoutput-sent',
            'taskoutput-sending',
            'taskoutput-queued'
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\WorkerBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();
        }
    }
}
