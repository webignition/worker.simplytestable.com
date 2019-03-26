<?php

namespace App\Tests\Functional\Services;

use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;

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

        if ($taskType instanceof Type) {
            $this->assertEquals($expectedIsSelectable, $taskType->isSelectable());

            if (empty($expectedChildTypeName)) {
                $this->assertNull($taskType->getChildType());
            } else {
                $childType = $taskType->getChildType();

                if ($childType instanceof Type) {
                    $this->assertEquals($expectedChildTypeName, $childType->getName());
                }
            }
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
