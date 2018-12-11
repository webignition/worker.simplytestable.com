<?php

namespace App\Tests\Unit\Event;

use App\Entity\Task\Task;
use App\Event\TaskReportCompletionFailureEvent;

class TaskReportCompletionFailureEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $failureType
     * @param int $statusCode
     */
    public function testCreate(string $failureType, int $statusCode)
    {
        $requestUrl = 'http://core-app/';

        $task = new Task();

        $event = new TaskReportCompletionFailureEvent($task, $failureType, $statusCode, $requestUrl);

        $this->assertSame(false, $event->isSucceeded());
        $this->assertSame($task, $event->getTask());
        $this->assertSame($failureType, $event->getFailureType());
        $this->assertSame($requestUrl, $event->getRequestUrl());
    }

    public function createDataProvider(): array
    {
        return [
            'http 404' => [
                'failureType' => TaskReportCompletionFailureEvent::FAILURE_TYPE_HTTP,
                'statusCode' => 404,
            ],
            'curl 28' => [
                'failureType' => TaskReportCompletionFailureEvent::FAILURE_TYPE_CURL,
                'statusCode' => 28,
            ],
        ];
    }
}
