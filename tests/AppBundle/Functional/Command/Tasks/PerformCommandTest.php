<?php

namespace Tests\AppBundle\Functional\Command\Tasks;

use GuzzleHttp\Psr7\Response;
use AppBundle\Command\Tasks\PerformCommand;
use AppBundle\Entity\Task\Task;
use AppBundle\Services\Resque\QueueService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\AppBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Tests\AppBundle\Factory\TestTaskFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\AppBundle\Services\HttpMockHandler;

class PerformCommandTest extends AbstractBaseTestCase
{
    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $httpMockHandler = self::$container->get(HttpMockHandler::class);
        $resqueQueueService = self::$container->get(QueueService::class);
        $this->clearRedis();

        $httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $testTaskFactory = new TestTaskFactory(self::$container);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));

        $this->assertEquals(Task::STATE_QUEUED, (string)$task->getState());

        $command = self::$container->get(PerformCommand::class);

        $returnCode = $command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(Task::STATE_COMPLETED, (string)$task->getState());

        $this->assertTrue($resqueQueueService->contains(
            'task-report-completion',
            [
                'id' => $task->getId(),
            ]
        ));
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
