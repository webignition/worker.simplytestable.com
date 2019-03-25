<?php

namespace App\Tests\Services;

use App\Entity\CachedResource;
use App\Model\Source;
use Doctrine\ORM\EntityManagerInterface;

class TaskSourceContentsLoader
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Source[] $sources
     *
     * @return string[]
     */
    public function load(array $sources): array
    {
        $contents = [];

        foreach ($sources as $source) {
            /* @var CachedResource $cachedResource */
            $cachedResource = $this->entityManager->find(CachedResource::class, $source->getValue());
            $contents[$source->getUrl()] = (string) stream_get_contents($cachedResource->getBody());
        }

        return $contents;
    }
}
