<?php

namespace App\Tests\Functional\Services;

use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Services\TaskPreparer;
use App\Tests\TestServices\TaskFactory;
use App\Entity\Task\Task;
use App\Tests\Functional\AbstractBaseTestCase;

class TaskPreparerTest extends AbstractBaseTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TypeInterface::TYPE_HTML_VALIDATION;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    /**
     * @var TaskPreparer
     */
    private $taskPreparer;

    /**
     * @var TaskFactory
     */
    private $testTaskFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskPreparer = self::$container->get(TaskPreparer::class);
        $this->testTaskFactory = self::$container->get(TaskFactory::class);
    }

    /**
     * @dataProvider prepareDataProvider
     *
     * @param array $taskValues
     */
    public function testPrepare($taskValues)
    {
        $task = $this->testTaskFactory->create($taskValues);

        $this->taskPreparer->prepare($task);

        $this->assertEquals(Task::STATE_PREPARED, $task->getState());
    }

    /**
     * @return array
     */
    public function prepareDataProvider()
    {
        return [
            'html validation' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    TaskFactory::DEFAULT_TASK_TYPE => Type::TYPE_HTML_VALIDATION,
                ]),
            ],
            'css validation' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    TaskFactory::DEFAULT_TASK_TYPE => Type::TYPE_CSS_VALIDATION,
                ]),
            ],
            'link integrity' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    TaskFactory::DEFAULT_TASK_TYPE => Type::TYPE_LINK_INTEGRITY,
                ]),
            ],
            'url discovery' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    TaskFactory::DEFAULT_TASK_TYPE => Type::TYPE_URL_DISCOVERY,
                ]),
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
