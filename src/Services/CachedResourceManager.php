<?php

namespace App\Services;

use App\Entity\CachedResource;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class CachedResourceManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ObjectRepository
     */
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(CachedResource::class);
    }

    public function persist(CachedResource $cachedResource)
    {
        $this->entityManager->persist($cachedResource);
        $this->entityManager->flush();
    }

    public function find(string $requestHash): ?CachedResource
    {
        $cachedResource = $this->repository->find($requestHash);

        return $cachedResource instanceof CachedResource ? $cachedResource : null;
    }

    public function remove(CachedResource $cachedResource)
    {
        $this->entityManager->remove($cachedResource);
        $this->entityManager->flush();
    }
}
