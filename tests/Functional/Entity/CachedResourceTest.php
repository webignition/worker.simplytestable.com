<?php

namespace App\Tests\Functional\Entity;

use App\Entity\CachedResource;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class CachedResourceTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $url
     * @param string $contentType
     * @param string $body
     */
    public function testCreate(string $url, string $contentType, string $body)
    {
        $resource = CachedResource::create($url, $contentType, $body);

        $this->assertEquals($url, $resource->getUrl());
        $this->assertEquals($contentType, $resource->getContentType());
        $this->assertEquals($body, stream_get_contents($resource->getBody()));

        $this->entityManager->persist($resource);
        $this->entityManager->flush();

        $id = $resource->getId();

        $this->entityManager->clear();

        /* @var CachedResource $retrievedResource */
        $retrievedResource = $this->entityManager->find(CachedResource::class, $id);

        $this->assertEquals($url, $retrievedResource->getUrl());
        $this->assertEquals($contentType, $retrievedResource->getContentType());
        $this->assertEquals($body, stream_get_contents($retrievedResource->getBody()));
    }

    public function createDataProvider(): array
    {
        return [
            'empty body' => [
                'url' => 'http://example.com/',
                'contentType' => '',
                'body' => '',
            ],
            'has body' => [
                'url' => 'http://example.com/',
                'contentType' => 'text/plain',
                'body' => 'body content',
            ],
            'has mb body' => [
                'url' => 'http://example.com/',
                'contentType' => 'text/plain',
                'body' => '내 호버크라프트는 뱀장어로 가득하다',
            ],
        ];
    }

    public function testUrlIsUnique()
    {
        $url = 'http://example.com/';

        $resource1 = CachedResource::create($url, 'text/plain', '');

        $this->entityManager->persist($resource1);
        $this->entityManager->flush();

        $resource2 = CachedResource::create($url, 'text/html', 'html content');
        $this->entityManager->persist($resource2);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->entityManager->flush();
    }
}
