<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Integration\Command\Task;

use App\Command\Task\PrepareCommand;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Services\TaskSourceContentsLoader;
use App\Tests\Services\TestTaskFactory;
use App\Services\Resque\QueueService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/Task/PrepareCommand
 */
class PrepareCommandTest extends AbstractBaseTestCase
{
    /**
     * @var PrepareCommand
     */
    private $command;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskSourceContentsLoader
     */
    private $taskSourceContentsLoader;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(PrepareCommand::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->taskSourceContentsLoader = self::$container->get(TaskSourceContentsLoader::class);
    }

    /**
     * @dataProvider runDataProvider
     */
    public function testRun(
        array $httpFixtures,
        array $taskValues,
        array $expectedSources,
        array $expectedSourceContents
    ) {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $httpMockHandler = self::$container->get(HttpMockHandler::class);

        $httpMockHandler->appendFixtures($httpFixtures);

        $task = $testTaskFactory->create($taskValues);

        $this->assertEquals(Task::STATE_QUEUED, $task->getState());

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(Task::STATE_PREPARED, $task->getState());

        $sources = $task->getSources();
        $this->assertEquals($expectedSources, $sources);

        $sourceContents = $this->taskSourceContentsLoader->load($sources);
        $this->assertEquals($expectedSourceContents, $sourceContents);

        $this->assertTrue(self::$container->get(QueueService::class)->contains(
            'task-perform',
            [
                'id' => $task->getId()
            ]
        ));
    }

    public function runDataProvider(): array
    {
        return [
            'html validation' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => '<doctype html>',
                ],
            ],
            'css validation, no stylesheets' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => '<doctype html>',
                ],
            ],
            'css validation, single linked stylesheet' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css'], 'html {}'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                    'http://example.com/style.css' => new Source(
                        'http://example.com/style.css',
                        Source::TYPE_CACHED_RESOURCE,
                        '10490a4daf45105812424ba6b4b77c36',
                        [
                            'origin' => 'resource',
                        ]
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => HtmlDocumentFactory::load('empty-body-single-css-link'),
                    'http://example.com/style.css' => 'html {}',
                ],
            ],
            'link integrity' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_LINK_INTEGRITY,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => '<doctype html>',
                ],
            ],
            'url discovery' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_URL_DISCOVERY,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => '<doctype html>',
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
