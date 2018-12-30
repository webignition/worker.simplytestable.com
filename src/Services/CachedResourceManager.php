<?php

namespace App\Services;

use App\Entity\CachedResource;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class CachedResourceManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(CachedResource::class);
    }

    public function persist(CachedResource $cachedResource)
    {
        $this->entityManager->persist($cachedResource);
        $this->entityManager->flush();
    }

    public function find(string $requestHash): ?CachedResource
    {
        return $this->entityRepository->find($requestHash);
    }

    public function remove(CachedResource $cachedResource)
    {
        $this->entityManager->remove($cachedResource);
        $this->entityManager->flush();
    }
}
