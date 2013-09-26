<?php

namespace SimplyTestable\WorkerBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\WorkerBundle\Entity\State;

class LoadStates extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }  
    
    private $stateDetails = array(
        'worker-active' => null,
        'worker-awaiting-activation-verification' => 'worker-active',
        'worker-new' => 'worker-awaiting-activation-verification',
    );  
    
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->stateDetails as $name => $nextStateName) {
            if (!$this->getStateService()->has($name)) {
                $state = new State();
                $state->setName($name);
                
                if (!is_null($nextStateName)) {
                    $state->setNextState($this->getStateService()->find($nextStateName));
                }
     
                $manager->persist($state);
                $manager->flush();                  
            }
        }
        
        
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
    
    
    /**
     * 
     * @return \SimplyTestable\WorkerBundle\Services\StateService
     */
    public function getStateService() {
        return $this->container->get('simplytestable.services.stateservice');
    }
}
