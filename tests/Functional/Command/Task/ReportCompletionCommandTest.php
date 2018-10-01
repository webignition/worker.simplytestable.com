<?php

namespace App\Tests\Functional\Command\Task;

use App\Command\Task\ReportCompletionCommand;
use App\Entity\Task\Task;
use App\Services\TaskPerformer;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\TestServices\TaskFactory;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

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
     * @var Task
     */
    private $task;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(ReportCompletionCommand::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $testTaskFactory = self::$container->get(TaskFactory::class);

        $this->task = $testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));

        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);
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
        $this->httpMockHandler->appendFixtures($responseFixtures);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        self::$container->get(TaskPerformer::class)->perform($this->task);
        $this->assertNotNull($this->task->getOutput()->getId());

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $this->task->getId()
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
                'responseFixtures' => array_fill(0, 6, $internalServerErrorResponse),
                'expectedCommandReturnCode' => 500,
            ],
            'curl 28' => [
                'responseFixtures' => array_fill(0, 6, $curl28ConnectException),
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
