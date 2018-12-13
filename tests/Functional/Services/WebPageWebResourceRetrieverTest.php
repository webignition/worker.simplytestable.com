<?php

namespace App\Tests\Functional\Services;

use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;

class WebPageWebResourceRetrieverTest extends AbstractBaseTestCase
{
    /**
     * @var WebResourceRetriever
     */
    private $webResourceRetriever;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->webResourceRetriever = self::$container->get('app.services.web-resource-retriever.web-page');
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    /**
     * @dataProvider retrieveValidContentTypeDataProvider
     *
     * @param string $responseContentType
     *
     * @throws InternetMediaTypeParseException
     * @throws HttpException
     * @throws InvalidResponseContentTypeException
     * @throws TransportException
     */
    public function testRetrieveValidContentType(string $responseContentType)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => $responseContentType]),
            new Response(200, ['content-type' => $responseContentType], '')
        ]);

        $resource = $this->webResourceRetriever->retrieve(new Request('GET', 'http://example.com/'));

        $this->assertInstanceOf(WebPage::class, $resource);
    }

    public function retrieveValidContentTypeDataProvider()
    {
        return [
            'text/html' => [
                'responseContentType' => 'text/html',
            ],
            'application/xml' => [
                'responseContentType' => 'application/xml',
            ],
            'text/xml' => [
                'responseContentType' => 'text/xml',
            ],
            'application/xhtml+xml' => [
                'responseContentType' => 'application/xhtml+xml',
            ],
        ];
    }

    /**
     * @dataProvider retrieveInvalidContentTypeDataProvider
     *
     * @param string $responseContentType
     *
     * @throws InternetMediaTypeParseException
     * @throws HttpException
     * @throws InvalidResponseContentTypeException
     * @throws TransportException
     */
    public function testRetrieveInvalidContentType(string $responseContentType)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => $responseContentType]),
        ]);

        $this->expectException(InvalidResponseContentTypeException::class);

        $this->webResourceRetriever->retrieve(new Request('GET', 'http://example.com/'));
    }

    public function retrieveInvalidContentTypeDataProvider()
    {
        return [
            'application/javascript' => [
                'responseContentType' => 'application/javascript',
            ],
            'text/css' => [
                'responseContentType' => 'text/css',
            ],
            'text/plain' => [
                'responseContentType' => 'text/plain',
            ],
        ];
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
    }
}
