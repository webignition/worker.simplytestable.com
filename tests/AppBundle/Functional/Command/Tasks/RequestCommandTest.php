<?php

namespace Tests\AppBundle\Functional\Command\Tasks;

use GuzzleHttp\Psr7\Response;
use AppBundle\Command\Tasks\RequestCommand;
use AppBundle\Services\Resque\QueueService;
use AppBundle\Services\TasksService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\AppBundle\Factory\ConnectExceptionFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\AppBundle\Services\HttpMockHandler;

/**
 * @group Command/Tasks/RequestCommand
 */
class RequestCommandTest extends AbstractBaseTestCase
{
    /**
     * @var RequestCommand
     */
    private $command;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(RequestCommand::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param bool $tasksServiceRequestReturnValue
     * @param int $expectedCommandReturnCode
     * @param bool $expectedQueueIsEmpty
     *
     * @throws \Exception
     */
    public function testExecute($tasksServiceRequestReturnValue, $expectedCommandReturnCode, $expectedQueueIsEmpty)
    {
        $this->clearRedis();

        $tasksService = self::$container->get(TasksService::class);
        $tasksService
            ->setRequestResult($tasksServiceRequestReturnValue);

        $returnCode = $this->command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            $expectedCommandReturnCode,
            $returnCode
        );

        $this->assertEquals(
            $expectedQueueIsEmpty,
            self::$container->get(QueueService::class)->isEmpty('tasks-request')
        );
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'success' => [
                'tasksServiceRequestReturnValue' => true,
                'expectedCommandReturnCode' => RequestCommand::RETURN_CODE_OK,
                'expectedQueueIsEmpty' => true,
            ],
            'outside threshold' => [
                'tasksServiceRequestReturnValue' => false,
                'expectedCommandReturnCode' => RequestCommand::RETURN_CODE_TASK_WORKLOAD_EXCEEDS_REQUEST_THRESHOLD,
                'expectedQueueIsEmpty' => false,
            ],
        ];
    }

    /**
     * @dataProvider executeRequestFailureDataProvider
     *
     * @param array $responseFixtures
     * @param int $expectedCommandReturnCode
     *
     * @throws \Exception
     */
    public function testExecuteRequestFailure($responseFixtures, $expectedCommandReturnCode)
    {
        $this->httpMockHandler->appendFixtures($responseFixtures);
        $this->clearRedis();

        $returnCode = $this->command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            $expectedCommandReturnCode,
            $returnCode
        );

        $this->assertFalse(self::$container->get(QueueService::class)->isEmpty('tasks-request'));
    }

    /**
     * @return array
     */
    public function executeRequestFailureDataProvider()
    {
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out.');

        return [
            'http 404' => [
                'responseFixtures' => [
                    new Response(404),
                ],
                'expectedCommandReturnCode' => RequestCommand::RETURN_CODE_FAILED,
            ],
            'curl 28' => [
                'responseFixtures' => [
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                ],
                'expectedCommandReturnCode' => RequestCommand::RETURN_CODE_FAILED,
            ],
        ];
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
    }
}
