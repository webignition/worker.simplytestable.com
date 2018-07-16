<?php

namespace Tests\AppBundle\Functional\Command\Task;

use GuzzleHttp\Psr7\Response;
use App\Command\Task\ReportCompletionCommand;
use App\Services\TaskService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\AppBundle\Factory\ConnectExceptionFactory;
use Tests\AppBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\AppBundle\Factory\TestTaskFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\AppBundle\Services\HttpMockHandler;

/**
 * @group Command/Task/ReportCompletionCommand
 */
class ReportCompletionCommandTest extends AbstractBaseTestCase
{
    /**
     * @var ReportCompletionCommand
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

        $this->command = self::$container->get(ReportCompletionCommand::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $responseFixtures
     * @param int $expectedCommandReturnCode
     * @throws \Exception
     */
    public function testRun($responseFixtures, $expectedCommandReturnCode)
    {
        $this->httpMockHandler->appendFixtures(array_merge([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ], $responseFixtures));

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $testTaskFactory = new TestTaskFactory(self::$container);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));
        $this->assertNotNull($task->getId());

        self::$container->get(TaskService::class)->perform($task);
        $this->assertNotNull($task->getOutput()->getId());

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $task->getId()
        ]), new NullOutput());

        $this->assertEquals(
            $expectedCommandReturnCode,
            $returnCode
        );
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        $internalServerErrorResponse = new Response(500);
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out.');

        return [
            'http 200' => [
                'responseFixtures' => [
                    new Response(200),
                ],
                'expectedCommandReturnCode' => 0,
            ],
            'http 404' => [
                'responseFixtures' => [
                    new Response(404),
                ],
                'expectedCommandReturnCode' => 404,
            ],
            'http 500' => [
                'responseFixtures' => [
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                ],
                'expectedCommandReturnCode' => 500,
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
                'expectedCommandReturnCode' => 28,
            ],
        ];
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
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
