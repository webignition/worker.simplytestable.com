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

    public function testFind()
    {
        $requestHash = 'request-hash';
        $url = 'http://example.com';
        $contentType = 'text/plain';
        $body = '';

        $this->assertNull($this->cachedResourceManager->find($requestHash));

        $this->cachedResourceManager->persist(CachedResource::create($requestHash, $url, $contentType, $body));
        $this->entityManager->clear();

        $cachedResource = $this->cachedResourceManager->find($requestHash);

        $this->assertInstanceOf(CachedResource::class, $cachedResource);
        $this->assertEquals($requestHash, $cachedResource->getRequestHash());
        $this->assertEquals($url, $cachedResource->getUrl());
        $this->assertEquals($contentType, $cachedResource->getContentType());
        $this->assertEquals($body, stream_get_contents($cachedResource->getBody()));
    }
}
