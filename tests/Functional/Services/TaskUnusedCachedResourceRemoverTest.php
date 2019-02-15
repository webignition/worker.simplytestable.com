<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\TaskUnusedCachedResourceRemover;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestTaskFactory;
use Doctrine\ORM\EntityManagerInterface;
use webignition\InternetMediaType\InternetMediaType;

class TaskUnusedCachedResourceRemoverTest extends AbstractBaseTestCase
{
    /**
     * @var TaskUnusedCachedResourceRemover
     */
    private $taskUnusedCachedResourceRemover;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * @var EntityManagerInterface
     */
    private $enityManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskUnusedCachedResourceRemover = self::$container->get(TaskUnusedCachedResourceRemover::class);
        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
        $this->enityManager = self::$container->get(EntityManagerInterface::class);
    }

    /**
     * @dataProvider removeDataProvider
     */
    public function testRemove(
        array $taskValuesCollection,
        array $expectedTaskSourcesCollection,
        array $expectedTaskCachedResourceStates
    ) {
        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($taskValuesCollection as $taskValues) {
            $tasks[] = $this->testTaskFactory->create($taskValues);
        }

        foreach ($expectedTaskSourcesCollection as $taskIndex => $expectedTaskSources) {
            $this->assertEquals($expectedTaskSources, $tasks[$taskIndex]->getSources());
        }

        $task = $tasks[0];
        $this->taskUnusedCachedResourceRemover->remove($task);

        foreach ($expectedTaskSourcesCollection as $taskIndex => $expectedTaskSources) {
            $this->assertEquals($expectedTaskSources, $tasks[$taskIndex]->getSources());
        }

        $sources = $task->getSources();

        $this->assertCount(count($expectedTaskCachedResourceStates), $sources);

//        foreach ($tasks as $task) {
//            $sources = $task->getSources();
//
////            var_dump($sources);
//
//            foreach ($sources as $source) {
//                var_dump($this->enityManager->find(CachedResource::class, $source->getValue()));
//            }
//        }

//        var_dump($sources);
//
//        foreach ($sources as $source) {
//            var_dump($this->enityManager->find(CachedResource::class, $source->getValue()));
//        }

        foreach ($expectedTaskCachedResourceStates as $sourceIndex => $expectedTaskCachedResourceState) {
            $source = $sources[$sourceIndex];
            $cachedResource = $this->enityManager->find(CachedResource::class, $source->getValue());

            if ('removed' === $expectedTaskCachedResourceState) {
                $this->assertNull($cachedResource);
            } else {
                $this->assertInstanceOf(CachedResource::class, $cachedResource);
            }
        }
    }

    public function removeDataProvider(): array
    {
        return [
            'single task, no sources' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([

                    ]),
                ],
                'expectedTaskSourcesCollection' => [
                    [],
                ],
                'expectedTaskCachedResourceStates' => [],
            ],
            'single task, single unused source' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'sources' => [
                            [
                                'url' => 'http://example.com',
                                'content' => '<!doctype html>',
                                'contentType' => new InternetMediaType('text', 'html'),
                            ],
                        ],
                    ]),
                ],
                'expectedTaskSourcesCollection' => [
                    [
                        'http://example.com' => new Source(
                            'http://example.com',
                            Source::TYPE_CACHED_RESOURCE,
                            '0d633f5a406af4dc8ebcc4201087bce6'
                        ),
                    ],
                ],
                'expectedTaskCachedResourceStates' => [
                    'http://example.com' => 'removed',
                ],
            ],
            'single task, multiple unused sources' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'sources' => [
                            [
                                'url' => 'http://example.com',
                                'content' => '<!doctype html>',
                                'contentType' => new InternetMediaType('text', 'html'),
                            ],
                            [
                                'url' => 'http://example.com/one.css',
                                'content' => 'html {}',
                                'contentType' => new InternetMediaType('text', 'css'),
                            ],
                            [
                                'url' => 'http://example.com/two.css',
                                'content' => 'body {}',
                                'contentType' => new InternetMediaType('text', 'css'),
                            ],
                        ],
                    ]),
                ],
                'expectedTaskSourcesCollection' => [
                    [
                        'http://example.com' => new Source(
                            'http://example.com',
                            Source::TYPE_CACHED_RESOURCE,
                            '0d633f5a406af4dc8ebcc4201087bce6'
                        ),
                        'http://example.com/one.css' =>  new Source(
                            'http://example.com/one.css',
                            Source::TYPE_CACHED_RESOURCE,
                            '7a39b475cf06e8626219dd25314c0e20'
                        ),
                        'http://example.com/two.css' =>  new Source(
                            'http://example.com/two.css',
                            Source::TYPE_CACHED_RESOURCE,
                            '71ccc1362462e64378b12fb9f1c30c02'
                        ),
                    ],
                ],
                'expectedTaskCachedResourceStates' => [
                    'http://example.com' => 'removed',
                    'http://example.com/one.css' => 'removed',
                    'http://example.com/two.css' => 'removed',
                ],
            ],
            'two tasks, single unused source, single shared source' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'type' => TypeInterface::TYPE_CSS_VALIDATION,
                        'state' => Task::STATE_PREPARED,
                        'sources' => [
                            [
                                'url' => 'http://example.com',
                                'content' => '<!doctype html>',
                                'contentType' => new InternetMediaType('text', 'html'),
                            ],
                            [
                                'url' => 'http://example.com/one.css',
                                'content' => 'html {}',
                                'contentType' => new InternetMediaType('text', 'css'),
                            ],
                        ],
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'type' => TypeInterface::TYPE_HTML_VALIDATION,
                        'state' => Task::STATE_PREPARED,
                        'sources' => [
                            [
                                'url' => 'http://example.com',
                                'content' => '<!doctype html>',
                                'contentType' => new InternetMediaType('text', 'html'),
                            ],
                        ],
                    ]),
                ],
                'expectedTaskSourcesCollection' => [
                    [
                        'http://example.com' => new Source(
                            'http://example.com',
                            Source::TYPE_CACHED_RESOURCE,
                            '0d633f5a406af4dc8ebcc4201087bce6'
                        ),
                        'http://example.com/one.css' =>  new Source(
                            'http://example.com/one.css',
                            Source::TYPE_CACHED_RESOURCE,
                            '7a39b475cf06e8626219dd25314c0e20'
                        ),
                    ],
                ],
                'expectedTaskCachedResourceStates' => [
                    'http://example.com' => 'not-removed',
                    'http://example.com/one.css' => 'removed',
                ],
            ],
        ];
    }
}
