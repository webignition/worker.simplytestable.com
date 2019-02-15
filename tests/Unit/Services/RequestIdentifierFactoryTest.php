<?php

namespace App\Tests\Unit\Services;

use App\Entity\Task\Task;
use App\Model\RequestIdentifier;
use App\Model\Task\Type;
use App\Services\RequestIdentifierFactory;

class RequestIdentifierFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestIdentifierFactory
     */
    private $requestIdentifierFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->requestIdentifierFactory = new RequestIdentifierFactory();
    }

    public function testCreateFromTask()
    {
        $taskUrl = 'http://example.com';
        $taskParameters = json_encode([
            'foo' => 'bar',
        ]);

        $task = Task::create(
            new Type(Type::TYPE_HTML_VALIDATION, true, null),
            $taskUrl,
            $taskParameters
        );

        $requestIdentifier = $this->requestIdentifierFactory->createFromTask($task);

        $this->assertInstanceOf(RequestIdentifier::class, $requestIdentifier);
        $this->assertEquals('200ba358c289f923fde315f55b688ab8', $requestIdentifier->getHash());
    }

    public function testCreateFromTaskSource()
    {
        $taskUrl = 'http://example.com';
        $resourceUrl = 'http://example.com/style.css';
        $taskParameters = json_encode([
            'foo' => 'bar',
        ]);

        $task = Task::create(
            new Type(Type::TYPE_HTML_VALIDATION, true, null),
            $taskUrl,
            $taskParameters
        );

        $requestIdentifier = $this->requestIdentifierFactory->createFromTaskResource($task, $resourceUrl);

        $this->assertInstanceOf(RequestIdentifier::class, $requestIdentifier);
        $this->assertEquals('5fc2e6bd14ea7bd11b0733d650ddc855', $requestIdentifier->getHash());
    }
}
