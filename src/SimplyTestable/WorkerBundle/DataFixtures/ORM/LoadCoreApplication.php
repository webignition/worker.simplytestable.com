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
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $coreApplicationUrl = $this->container->getParameter('core_url');
        $repository = $manager->getRepository('SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication');

        $coreApplicationList = $repository->findAll();
        $coreApplication = (count($coreApplicationList)) ? $coreApplicationList[0] : new CoreApplication();

        if ($coreApplication->getUrl() != $coreApplicationUrl) {
            $coreApplication->setUrl($coreApplicationUrl);
            $manager->persist($coreApplication);
            $manager->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2; // the order in which fixtures will be loaded
    }
}
