<?php

namespace App\Tests\Functional\Command\Tasks;

use App\Services\TaskPerformanceService;
use App\Tests\TestServices\TaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Command\Tasks\ReportCompletionCommand;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use Symfony\Component\Console\Input\ArrayInput;
use App\Tests\Services\HttpMockHandler;

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
     * @throws \Exception
     */
    public function testRun()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
            new Response(200),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $testTaskFactory = self::$container->get(TaskFactory::class);

        $task = $testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));
        $this->assertNotNull($task->getId());

        self::$container->get(TaskPerformanceService::class)->perform($task);
        $this->assertNotNull($task->getOutput()->getId());

        $returnCode = $this->command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        $this->assertEquals(
            0,
            $returnCode
        );

        $this->assertNull($task->getOutput()->getId());
        $this->assertNull($task->getId());
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
