<?php

namespace SimplyTestable\WorkerBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication;

class LoadCoreApplication extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
    
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $coreApplication = new CoreApplication();
        $coreApplication->setUrl($this->container->getParameter('core_url'));
        $manager->persist($coreApplication);
        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2; // the order in which fixtures will be loaded
    }
}
