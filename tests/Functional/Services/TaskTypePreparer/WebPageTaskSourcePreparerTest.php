<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Exception\UnableToRetrieveResourceException;
use App\Model\Source;
use App\Model\Task\Type;
use App\Services\TaskSourceRetriever;
use App\Services\TaskTypePreparer\WebPageTaskSourcePreparer;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TaskTypeRetriever;

class WebPageTaskSourcePreparerTest extends AbstractBaseTestCase
{
    const TASK_URL = 'http://example.com/';

    /**
     * @var WebPageTaskSourcePreparer
     */
    private $preparer;

    /**
     * @var Task
     */
    private $task;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->preparer = self::$container->get(WebPageTaskSourcePreparer::class);
        $this->task = $this->createTask();
    }

    public function testInvoke()
    {
        $taskSourceRetriever = \Mockery::mock(TaskSourceRetriever::class);
        $taskSourceRetriever
            ->shouldNotReceive('retrieve');

        $source = new Source(self::TASK_URL, Source::TYPE_CACHED_RESOURCE, 'request-hash');
        $this->task->addSource($source);

        $this->setTaskSourceRetrieverOnWebPageTaskSourcePreparer($taskSourceRetriever);

        $taskEvent = new TaskEvent($this->task);

        $this->preparer->__invoke($taskEvent);
        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function testPrepareHasExistingSource()
    {
        $taskSourceRetriever = \Mockery::mock(TaskSourceRetriever::class);
        $taskSourceRetriever
            ->shouldNotReceive('retrieve');

        $source = new Source(self::TASK_URL, Source::TYPE_CACHED_RESOURCE, 'request-hash');
        $this->task->addSource($source);

        $this->setTaskSourceRetrieverOnWebPageTaskSourcePreparer($taskSourceRetriever);

        $this->preparer->prepare($this->task);
        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function testPrepareNoExistingSource()
    {
        $taskSourceRetriever = \Mockery::mock(TaskSourceRetriever::class);
        $taskSourceRetriever
            ->shouldReceive('retrieve')
            ->with(
                self::$container->get('app.services.web-resource-retriever.web-page'),
                $this->task,
                self::TASK_URL
            )
            ->andReturn(true);

        $this->setTaskSourceRetrieverOnWebPageTaskSourcePreparer($taskSourceRetriever);

        $this->preparer->prepare($this->task);
        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function testPrepareCannotAcquireLock()
    {
        $taskSourceRetriever = \Mockery::mock(TaskSourceRetriever::class);
        $taskSourceRetriever
            ->shouldReceive('retrieve')
            ->with(
                self::$container->get('app.services.web-resource-retriever.web-page'),
                $this->task,
                self::TASK_URL
            )
            ->andReturn(false);

        $this->setTaskSourceRetrieverOnWebPageTaskSourcePreparer($taskSourceRetriever);

        $this->expectException(UnableToRetrieveResourceException::class);

        $this->preparer->prepare($this->task);
    }

    private function createTask(): Task
    {
        $taskTypeRetriever = self::$container->get(TaskTypeRetriever::class);

        return Task::create($taskTypeRetriever->retrieve(Type::TYPE_HTML_VALIDATION), self::TASK_URL);
    }

    private function setTaskSourceRetrieverOnWebPageTaskSourcePreparer(TaskSourceRetriever $taskSourceRetriever)
    {
        ObjectReflector::setProperty(
            $this->preparer,
            WebPageTaskSourcePreparer::class,
            'taskSourceRetriever',
            $taskSourceRetriever
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
