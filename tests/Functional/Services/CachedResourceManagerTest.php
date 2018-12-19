<?php

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Services\CachedResourceManager;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class CachedResourceManagerTest extends AbstractBaseTestCase
{
    /**
     * @var CachedResourceManager
     */
    private $cachedResourceManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->cachedResourceManager = self::$container->get(CachedResourceManager::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->entityRepository = $this->entityManager->getRepository(CachedResource::class);
    }

    public function testPersist()
    {
        $this->assertEmpty($this->entityRepository->findAll());

        $requestHash = 'request-hash';
        $cachedResource = CachedResource::create($requestHash, 'http://example.com', 'text/plain', '');

        $this->cachedResourceManager->persist($cachedResource);

        $this->assertNotEmpty($this->entityRepository->findAll());
    }
}
