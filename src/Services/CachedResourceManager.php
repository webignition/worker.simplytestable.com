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

    public function find(string $url): ?CachedResource
    {
        return $this->entityRepository->findOneBy([
            'urlHash' => CachedResource::createUrlHash($url),
        ]);
    }
}
