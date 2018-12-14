<?php

namespace App\Tests\Functional\EventListener;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Entity\TimePeriod;
use App\Event\TaskEvent;
use App\Event\TaskReportCompletionFailureEvent;
use App\Event\TaskReportCompletionSuccessEvent;
use App\EventListener\TaskReportedCompletionEventListener;
use App\Model\Task\Type;
use App\Services\TaskService;
use App\Tests\Services\ObjectPropertySetter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskReportedCompletionEventListenerTest extends AbstractTaskEventListenerTest
{
    public function testInvokeForTaskReportCompletionSuccessEvent()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $taskService = self::$container->get(TaskService::class);
        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);

        $taskType = new Type(Type::TYPE_HTML_VALIDATION, true, null);

        $task = $taskService->create('http://example.com/', $taskType, '');
        $task->setOutput(new Output());
        $task->setTimePeriod(new TimePeriod());

        try {
            $entityManager->persist($task);
            $entityManager->flush();
        } catch (ORMException $e) {
        }

        $this->assertNotNull($task->getId());
        $this->assertNotNull($task->getOutput()->getId());

        $eventDispatcher->dispatch(
            TaskEvent::TYPE_REPORTED_COMPLETION,
            new TaskReportCompletionSuccessEvent($task)
        );

        $this->assertNull($task->getId());
        $this->assertNull($task->getOutput()->getId());
    }

    /**
     * @dataProvider invokeForTaskReportCompletionFailureEventDataProvider
     *
     * @param TaskReportCompletionFailureEvent $event
     * @param string $expectedLogMessage
     * @param array $expectedLogContext
     */
    public function testInvokeForTaskReportCompletionFailureEvent(
        TaskReportCompletionFailureEvent $event,
        string $expectedLogMessage,
        array $expectedLogContext
    ) {
        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        $logger = \Mockery::mock(LoggerInterface::class);

        $taskReportedCompletionEventListener = self::$container->get(TaskReportedCompletionEventListener::class);

        ObjectPropertySetter::setProperty(
            $taskReportedCompletionEventListener,
            TaskReportedCompletionEventListener::class,
            'logger',
            $logger
        );

        $logger
            ->shouldReceive('error')
            ->withArgs(function (string $message, array $context) use ($expectedLogMessage, $expectedLogContext) {
                $this->assertSame($expectedLogMessage, $message);
                $this->assertSame($expectedLogContext, $context);

                return true;
            })
            ->once();

        $eventDispatcher->dispatch(TaskEvent::TYPE_REPORTED_COMPLETION, $event);
    }

    public function invokeForTaskReportCompletionFailureEventDataProvider(): array
    {
        $task = new Task();
        ObjectPropertySetter::setProperty($task, Task::class, 'id', self::TASK_ID);

        return [
            'http 404' => [
                'event' => new TaskReportCompletionFailureEvent(
                    $task,
                    TaskReportCompletionFailureEvent::FAILURE_TYPE_HTTP,
                    404,
                    'http://core-app/'
                ),
                'expectedLogMessage' => 'task-report-completion failed: [' . self::TASK_ID . ']',
                'expectedLogContext' => [
                    'request_url' => 'http://core-app/',
                    'failure_type' => TaskReportCompletionFailureEvent::FAILURE_TYPE_HTTP,
                    'status_code' => 404,
                ],
            ],
            'curl 28' => [
                'event' => new TaskReportCompletionFailureEvent(
                    $task,
                    TaskReportCompletionFailureEvent::FAILURE_TYPE_CURL,
                    28,
                    'http://core-app/'
                ),
                'expectedLogMessage' => 'task-report-completion failed: [' . self::TASK_ID . ']',
                'expectedLogContext' => [
                    'request_url' => 'http://core-app/',
                    'failure_type' => TaskReportCompletionFailureEvent::FAILURE_TYPE_CURL,
                    'status_code' => 28,
                ],
            ],
            'unknown' => [
                'event' => new TaskReportCompletionFailureEvent(
                    $task,
                    TaskReportCompletionFailureEvent::FAILURE_TYPE_UNKNOWN,
                    0,
                    'http://core-app/'
                ),
                'expectedLogMessage' => 'task-report-completion failed: [' . self::TASK_ID . ']',
                'expectedLogContext' => [
                    'request_url' => 'http://core-app/',
                    'failure_type' => TaskReportCompletionFailureEvent::FAILURE_TYPE_UNKNOWN,
                    'status_code' => 0,
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
