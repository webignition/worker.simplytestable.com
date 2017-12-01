<?php

namespace Tests\WorkerBundle\Functional\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\Tasks\ReportCompletionCommand;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Tests\WorkerBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class ReportCompletionCommandTest extends AbstractBaseTestCase
{
    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html;",
            "HTTP/1.1 200 OK\nContent-type:text/html;\n\n<!doctype html>",
            "HTTP/1.1 200 OK",
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $testTaskFactory = new TestTaskFactory($this->container);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));
        $this->assertNotNull($task->getId());

        $this->container->get(TaskService::class)->perform($task);
        $this->assertNotNull($task->getOutput()->getId());

        $command = $this->container->get(ReportCompletionCommand::class);

        $returnCode = $command->run(
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
}
