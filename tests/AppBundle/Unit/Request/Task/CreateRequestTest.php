<?php

namespace Tests\AppBundle\Unit\Request\Task;

use AppBundle\Entity\Task\Type\Type as TaskType;
use AppBundle\Request\Task\CreateRequest;

class CreateRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param TaskType|null $taskType
     * @param string|null $url
     * @param string|null $parameters
     * @param bool $expectedIsValid
     * @param TaskType|null $expectedTaskType
     * @param string|null $expectedUrl
     * @param string $expectedParameters
     */
    public function testCreate(
        $taskType,
        $url,
        $parameters,
        $expectedIsValid,
        $expectedTaskType,
        $expectedUrl,
        $expectedParameters
    ) {
        $createRequest = new CreateRequest($taskType, $url, $parameters);

        $this->assertEquals($expectedIsValid, $createRequest->isValid());
        $this->assertEquals($expectedTaskType, $createRequest->getTaskType());
        $this->assertEquals($expectedUrl, $createRequest->getUrl());
        $this->assertEquals($expectedParameters, $createRequest->getParameters());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $taskType = new TaskType();

        return [
            'empty task type is invalid' => [
                'taskType' => null,
                'url' => 'http://example.com/',
                'parameters' => null,
                'expectedIsValid' => false,
                'expectedTaskType' => null,
                'expectedUrl' => 'http://example.com/',
                'expectedParameters' => '',
            ],
            'empty url is invalid' => [
                'taskType' => $taskType,
                'url' => null,
                'parameters' => null,
                'expectedIsValid' => false,
                'expectedTaskType' => $taskType,
                'expectedUrl' => '',
                'expectedParameters' => '',
            ],
            'valid' => [
                'taskType' => $taskType,
                'url' => 'http://example.com/',
                'parameters' => null,
                'expectedIsValid' => true,
                'expectedTaskType' => $taskType,
                'expectedUrl' => 'http://example.com/',
                'expectedParameters' => '',
            ],
            'valid with parameters' => [
                'taskType' => $taskType,
                'url' => 'http://example.com/',
                'parameters' => 'foo',
                'expectedIsValid' => true,
                'expectedTaskType' => $taskType,
                'expectedUrl' => 'http://example.com/',
                'expectedParameters' => 'foo',
            ],
        ];
    }
}
