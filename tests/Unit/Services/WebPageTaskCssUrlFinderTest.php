<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Unit\Services;

use App\Entity\Task\Task;
use App\Exception\UnableToPerformTaskException;
use App\Services\CachedResourceManager;
use App\Services\CssSourceInspector;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\WebPageTaskCssUrlFinder;

class WebPageTaskCssUrlFinderTest extends \PHPUnit\Framework\TestCase
{
    public function testFindUnableToRetrieveCachedWebPage()
    {
        $task = \Mockery::mock(Task::class);

        $taskCachedSourceWebPageRetriever = \Mockery::mock(TaskCachedSourceWebPageRetriever::class);
        $taskCachedSourceWebPageRetriever
            ->shouldReceive('retrieve')
            ->with($task)
            ->andReturn(null);

        $webPageTaskCssUrlFinder = new WebPageTaskCssUrlFinder(
            $taskCachedSourceWebPageRetriever,
            \Mockery::mock(CssSourceInspector::class),
            \Mockery::mock(CachedResourceManager::class)
        );

        $this->expectException(UnableToPerformTaskException::class);

        $webPageTaskCssUrlFinder->find($task);
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
