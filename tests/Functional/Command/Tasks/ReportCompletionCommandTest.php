<?php

namespace App\Tests\Functional\Command\Tasks;

use App\Entity\Task\Output;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use Doctrine\ORM\EntityManagerInterface;
use App\Command\Task\ReportCompletionCommand as TaskReportCompletionCommand;
use App\Command\Tasks\ReportCompletionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\InternetMediaType\InternetMediaType;

class ReportCompletionCommandTest extends AbstractBaseTestCase
{
    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $command = self::$container->get(ReportCompletionCommand::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));

        $task->setOutput(Output::create(
            '',
            new InternetMediaType('application', 'json')
        ));

        $entityManager->persist($task);
        $entityManager->flush();

        $taskId = $task->getId();

        $taskReportCompletionCommand = \Mockery::mock(TaskReportCompletionCommand::class);
        $taskReportCompletionCommand
            ->shouldReceive('run')
            ->withArgs(function (InputInterface $input, OutputInterface $output) use ($taskId) {
                $reflector = new \ReflectionClass(ArrayInput::class);
                $property = $reflector->getProperty('parameters');
                $property->setAccessible(true);
                $inputParameters = $property->getValue($input);

                $this->assertEquals(
                    [
                        'id' => $taskId,
                    ],
                    $inputParameters
                );

                $this->assertInstanceOf(BufferedOutput::class, $output);

                return true;
            });

        ObjectReflector::setProperty(
            $command,
            ReportCompletionCommand::class,
            'taskReportCompletionCommand',
            $taskReportCompletionCommand
        );

        $returnCode = $command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
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
