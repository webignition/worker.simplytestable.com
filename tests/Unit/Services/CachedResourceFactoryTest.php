<?php

namespace App\Tests\Unit\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Task\Type;
use App\Services\CachedResourceFactory;
use App\Services\RequestIdentifierFactory;
use webignition\WebResource\WebPage\WebPage;

class CachedResourceFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CachedResourceFactory
     */
    private $cachedResourceFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->cachedResourceFactory = new CachedResourceFactory();
    }

    public function testCreate()
    {
        $requestIdentifierFactory = new RequestIdentifierFactory();

        $url = 'http://example.com';
        $parameters = (string) json_encode([
            'foo' => 'bar',
        ]);

        $task = Task::create(
            new Type(Type::TYPE_HTML_VALIDATION, true, null),
            $url,
            $parameters
        );

        $webPageContent = 'content';

        /* @var WebPage $webPage */
        /** @noinspection PhpUnhandledExceptionInspection */
        $webPage = WebPage::createFromContent($webPageContent);

        $requestIdentifier = $requestIdentifierFactory->createFromTask($task);
        $requestHash = (string) $requestIdentifier;

        $cachedResource = $this->cachedResourceFactory->create($requestHash, $task->getUrl(), $webPage);

        $this->assertInstanceOf(CachedResource::class, $cachedResource);
        $this->assertEquals($requestHash, $cachedResource->getRequestHash());
        $this->assertEquals($url, $cachedResource->getUrl());
        $this->assertEquals('text/html', $cachedResource->getContentType());
        $this->assertEquals($webPageContent, stream_get_contents($cachedResource->getBody()));
    }
}
