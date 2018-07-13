<?php

namespace Tests\AppBundle\Functional\Command\Tasks;

use GuzzleHttp\Psr7\Response;
use AppBundle\Command\Tasks\ReportCompletionCommand;
use AppBundle\Services\TaskService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Tests\AppBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\AppBundle\Factory\TestTaskFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\AppBundle\Services\HttpMockHandler;

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

        $testTaskFactory = new TestTaskFactory(self::$container);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));
        $this->assertNotNull($task->getId());

        self::$container->get(TaskService::class)->perform($task);
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
