<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\TaskTypePerformer\WebPageTaskContentEncodingExaminer;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\ContentTypeFactory;
use App\Tests\Services\ObjectPropertySetter;
use App\Tests\Services\TestTaskFactory;
use webignition\WebResource\WebPage\WebPage;

class WebPageTaskContentEncodingExaminerTest extends AbstractBaseTestCase
{
    /**
     * @var WebPageTaskContentEncodingExaminer
     */
    private $examiner;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->examiner = self::$container->get(WebPageTaskContentEncodingExaminer::class);
    }

    /**
     * @dataProvider performNoChangesDataProvider
     */
    public function testPerformNoChanges(callable $webPageCreator)
    {
        $webPage = $webPageCreator();

        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $task = $testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $taskCachedResourceWebPageRetriever = \Mockery::mock(TaskCachedSourceWebPageRetriever::class);
        $taskCachedResourceWebPageRetriever
            ->shouldReceive('retrieve')
            ->with($task)
            ->andReturn($webPage);

        ObjectPropertySetter::setProperty(
            $this->examiner,
            WebPageTaskContentEncodingExaminer::class,
            'taskCachedSourceWebPageRetriever',
            $taskCachedResourceWebPageRetriever
        );

        $taskState = $task->getState();

        $this->examiner->perform($task);

        $this->assertEquals($taskState, $task->getState());
        $this->assertNull($task->getOutput());
    }

    public function performNoChangesDataProvider()
    {
        return [
            'task has no web page primary source' => [
                'webPageCreator' => function () {
                    return null;
                },
            ],
            'task as valid web page as primary source' => [
                'webPageCreator' => function (): WebPage {
                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        'web page content'
                    );

                    return $webPage;
                },
            ],
        ];
    }

    /**
     * @dataProvider performSetsTaskAsFailedDataProvider
     */
    public function testPerformSetsTaskAsFailed(callable $webPageCreator, string $expectedCharacterSetInOutput)
    {
        /* @var WebPage $webPage */
        $webPage = $webPageCreator();

        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $task = $testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $taskCachedResourceWebPageRetriever = \Mockery::mock(TaskCachedSourceWebPageRetriever::class);
        $taskCachedResourceWebPageRetriever
            ->shouldReceive('retrieve')
            ->with($task)
            ->andReturn($webPage);

        ObjectPropertySetter::setProperty(
            $this->examiner,
            WebPageTaskContentEncodingExaminer::class,
            'taskCachedSourceWebPageRetriever',
            $taskCachedResourceWebPageRetriever
        );

        $this->examiner->perform($task);

        $this->assertEquals(Task::STATE_FAILED_NO_RETRY_AVAILABLE, $task->getState());

        $taskOutput = $task->getOutput();
        $this->assertInstanceOf(Output::class, $taskOutput);
        $this->assertEquals(1, $taskOutput->getErrorCount());
        $this->assertEquals(0, $taskOutput->getWarningCount());
        $this->assertEquals(json_encode([
            'messages' => [
                [
                    'message' => $expectedCharacterSetInOutput,
                    'messageId' => 'invalid-character-encoding',
                    'type' => 'error',
                ],
            ],
        ]), $taskOutput->getOutput());
    }

    public function performSetsTaskAsFailedDataProvider()
    {
        return [
            'Invalid two-octet sequence with no specific encoding' => [
                'webPageCreator' => function (): WebPage {
                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        "\xc3\x28"
                    );

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'utf-8',
            ],
            'Invalid two-octet sequence with utf-8 encoding' => [
                'webPageCreator' => function (): WebPage {
                    $contentTypeFactory = self::$container->get(ContentTypeFactory::class);

                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        "\xc3\x28"
                    );

                    $webPage->setContentType($contentTypeFactory->createContentType('text/html; charset=utf-8'));

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'utf-8',
            ],
            'Invalid two-octet sequence with utf-16 encoding' => [
                'webPageCreator' => function (): WebPage {
                    $contentTypeFactory = self::$container->get(ContentTypeFactory::class);

                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        "\xc3\x28"
                    );

                    $webPage = $webPage->setContentType(
                        $contentTypeFactory->createContentType('text/html; charset=utf-16')
                    );

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'utf-16',
            ],
            'document with invalid windows-1251 encoding, no charset in page content type' => [
                'webPageCreator' => function (): WebPage {
                    $contentTypeFactory = self::$container->get(ContentTypeFactory::class);

                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        HtmlDocumentFactory::load('invalid-windows-1251-encoding')
                    );

                    $webPage = $webPage->setContentType(
                        $contentTypeFactory->createContentType('text/html')
                    );

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'windows-1251',
            ],
            'document with invalid windows-1251 encoding, charset in page content type is correctly ignored' => [
                'webPageCreator' => function (): WebPage {
                    $contentTypeFactory = self::$container->get(ContentTypeFactory::class);

                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        HtmlDocumentFactory::load('invalid-windows-1251-encoding')
                    );

                    $webPage = $webPage->setContentType(
                        $contentTypeFactory->createContentType('text/html; charset=utf-8')
                    );

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'windows-1251',
            ],
        ];
    }

    public function testHandles()
    {
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_HTML_VALIDATION));
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_CSS_VALIDATION));
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_LINK_INTEGRITY));
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL));
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_URL_DISCOVERY));
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            self::$container->getParameter('web_page_task_content_encoding_examiner_priority'),
            $this->examiner->getPriority()
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
