<?php

namespace App\Tests\Functional\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\Type;
use App\Services\SourceFactory;
use App\Services\TaskTypePreparer\WebPageTaskSourcePreparer;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use GuzzleHttp\Psr7\Response;

class WebPageTaskSourcePreparerTest extends AbstractBaseTestCase
{
    /**
     * @var WebPageTaskSourcePreparer
     */
    private $preparer;

    /**
     * @var SourceFactory
     */
    private $sourceFactory;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->preparer = self::$container->get(WebPageTaskSourcePreparer::class);
        $this->sourceFactory = self::$container->get(SourceFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
    }

    /**
     * @dataProvider prepareInvalidContentTypeDataProvider
     *
     * @param string $contentType
     */
    public function testPrepareInvalidContentType(string $contentType)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => $contentType]),
        ]);

        $taskTypeService = self::$container->get(TaskTypeService::class);

        $url = 'http://example.com';
        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);

        $this->assertEquals([], $task->getSources());

        $this->preparer->prepare($task);

        $expectedSource = $this->sourceFactory->createInvalidSource($url, 'invalid-content-type');

        $this->assertEquals(
            [
                $url => $expectedSource,
            ],
            $task->getSources()
        );
    }

    public function prepareInvalidContentTypeDataProvider(): array
    {
        return [
            'disallowed content type' => [
                'contentType' => 'text/plain',
            ],
            'unparseable content type' => [
                'contentType' => 'f o o',
            ],
        ];
    }
}
