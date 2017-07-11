<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

abstract class EntityService
{
    /**
      * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    abstract protected function getEntityName();

    /**
     * @param string $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return EntityRepository
     */
    public function getEntityRepository()
    {
        if (is_null($this->entityRepository)) {
            $this->entityRepository = $this->entityManager->getRepository($this->getEntityName());
        }

        return $this->entityRepository;
    }
}
