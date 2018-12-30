<?php

namespace App\Tests\Functional\Command\Tasks;

use App\Model\Task\TypeInterface;
use App\Tests\Services\ObjectPropertySetter;
use App\Tests\Services\TestTaskFactory;
use App\Command\Task\PerformCommand as TaskPerformCommand;
use App\Command\Tasks\PerformCommand;
use App\Entity\Task\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class PerformCommandTest extends AbstractBaseTestCase
{
    /**
     * @throws \Exception
     */
    public function testRun()
    {
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));

        $taskId = $task->getId();

        $this->assertEquals(Task::STATE_QUEUED, $task->getState());

        $taskPerformCommand = \Mockery::mock(TaskPerformCommand::class);
        $taskPerformCommand
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

        $command = self::$container->get(PerformCommand::class);

        ObjectPropertySetter::setProperty(
            $command,
            PerformCommand::class,
            'taskPerformCommand',
            $taskPerformCommand
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
