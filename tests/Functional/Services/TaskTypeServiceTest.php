<?php

namespace App\Tests\Functional\Services;

use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Services\TaskTypeService;
use App\Tests\TestServices\TaskFactory;
use Doctrine\ORM\OptimisticLockException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use App\Entity\Task\Task;
use App\Services\HttpRetryMiddleware;
use App\Services\TaskService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Utility\File;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class TaskTypeServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    protected function setUp()
    {
        parent::setUp();

        $this->taskTypeService = self::$container->get(TaskTypeService::class);
    }

    public function testGetForInvalidTaskType()
    {
        $this->assertNull($this->taskTypeService->get('invalid task type'));
    }

    /**
     * @dataProvider getForValidTaskTypeDataProvider
     *
     * @param string $taskTypeName
     * @param bool $expectedIsSelectable
     * @param string $expectedChildTypeName
     */
    public function testGetForValidTaskType(
        string $taskTypeName,
        bool $expectedIsSelectable,
        ?string $expectedChildTypeName
    ) {
        $taskType = $this->taskTypeService->get($taskTypeName);

        $this->assertInstanceOf(Type::class, $taskType);
        $this->assertEquals($expectedIsSelectable, $taskType->isSelectable());

        if (empty($expectedChildTypeName)) {
            $this->assertNull($taskType->getChildType());
        } else {
            $this->assertEquals($expectedChildTypeName, $taskType->getChildType()->getName());
        }
    }

    public function getForValidTaskTypeDataProvider(): array
    {
        return [
            'html validation' => [
                'taskTypeName' => TypeInterface::TYPE_HTML_VALIDATION,
                'expectedIsSelectable' => true,
                'expectedChildTypeName' => null,
            ],
            'css validation' => [
                'taskTypeName' => TypeInterface::TYPE_CSS_VALIDATION,
                'expectedIsSelectable' => true,
                'expectedChildTypeName' => null,
            ],
            'url discovery' => [
                'taskTypeName' => TypeInterface::TYPE_URL_DISCOVERY,
                'expectedIsSelectable' => true,
                'expectedChildTypeName' => null,
            ],
            'link integrity' => [
                'taskTypeName' => TypeInterface::TYPE_LINK_INTEGRITY,
                'expectedIsSelectable' => true,
                'expectedChildTypeName' => TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL,
            ],
            'link integrity single-url' => [
                'taskTypeName' => TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL,
                'expectedIsSelectable' => false,
                'expectedChildTypeName' => null,
            ],
        ];
    }
}
