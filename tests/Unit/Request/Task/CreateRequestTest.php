<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Request\Task;

use App\Model\Task\TypeInterface;
use App\Request\Task\CreateRequest;

class CreateRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        TypeInterface $type,
        string $url,
        string $parameters,
        bool $expectedIsValid
    ) {
        $createRequest = new CreateRequest($url, $type, $parameters);

        $this->assertEquals($expectedIsValid, $createRequest->isValid());
        $this->assertSame($type, $createRequest->getTaskType());
        $this->assertEquals($url, $createRequest->getUrl());
        $this->assertEquals($parameters, $createRequest->getParameters());
    }

    public function createDataProvider(): array
    {
        $taskType = \Mockery::mock(TypeInterface::class);

        return [
            'empty url is invalid' => [
                'taskType' => $taskType,
                'url' => '',
                'parameters' => '',
                'expectedIsValid' => false,
            ],
            'valid' => [
                'taskType' => $taskType,
                'url' => 'http://example.com/',
                'parameters' => '',
                'expectedIsValid' => true,
            ],
            'valid with parameters' => [
                'taskType' => $taskType,
                'url' => 'http://example.com/',
                'parameters' => 'foo',
                'expectedIsValid' => true,
            ],
        ];
    }
}
