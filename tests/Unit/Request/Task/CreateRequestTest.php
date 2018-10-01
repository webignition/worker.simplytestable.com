<?php

namespace App\Tests\Unit\Request\Task;

use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Request\Task\CreateRequest;

class CreateRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $taskTypeName
     * @param string|null $url
     * @param string|null $parameters
     * @param bool $expectedIsValid
     * @param string|null $expectedTaskType
     * @param string $expectedUrl
     * @param string $expectedParameters
     */
    public function testCreate(
        string $taskTypeName,
        ?string $url,
        ?string $parameters,
        bool $expectedIsValid,
        ?string $expectedTaskType,
        string $expectedUrl,
        string $expectedParameters
    ) {
        $taskType = empty($taskTypeName)
            ? null
            : new Type($taskTypeName);

        $createRequest = new CreateRequest($url, $taskType, $parameters);

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
        return [
            'empty task type is invalid' => [
                'taskTypeName' => '',
                'url' => 'http://example.com/',
                'parameters' => null,
                'expectedIsValid' => false,
                'expectedTaskType' => null,
                'expectedUrl' => 'http://example.com/',
                'expectedParameters' => '',
            ],
            'empty url is invalid' => [
                'taskTypeName' => TypeInterface::TYPE_HTML_VALIDATION,
                'url' => '',
                'parameters' => null,
                'expectedIsValid' => false,
                'expectedTaskType' => TypeInterface::TYPE_HTML_VALIDATION,
                'expectedUrl' => '',
                'expectedParameters' => '',
            ],
            'valid' => [
                'taskTypeName' => TypeInterface::TYPE_HTML_VALIDATION,
                'url' => 'http://example.com/',
                'parameters' => null,
                'expectedIsValid' => true,
                'expectedTaskType' => TypeInterface::TYPE_HTML_VALIDATION,
                'expectedUrl' => 'http://example.com/',
                'expectedParameters' => '',
            ],
            'valid with parameters' => [
                'taskTypeName' => TypeInterface::TYPE_HTML_VALIDATION,
                'url' => 'http://example.com/',
                'parameters' => 'foo',
                'expectedIsValid' => true,
                'expectedTaskType' => TypeInterface::TYPE_HTML_VALIDATION,
                'expectedUrl' => 'http://example.com/',
                'expectedParameters' => 'foo',
            ],
        ];
    }
}
