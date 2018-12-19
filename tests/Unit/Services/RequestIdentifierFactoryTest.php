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
        $url = 'http://example.com';
        $parameters = json_encode([
            'foo' => 'bar',
        ]);

        $task = Task::create(
            new Type(Type::TYPE_HTML_VALIDATION, true, null),
            $url,
            $parameters
        );

        $requestIdentifier = $this->requestIdentifierFactory->createFromTask($task);

        $this->assertInstanceOf(RequestIdentifier::class, $requestIdentifier);
    }
}
