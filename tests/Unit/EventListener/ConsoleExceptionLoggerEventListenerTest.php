<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\ConsoleExceptionLoggerEventListener;
use App\Tests\Functional\AbstractBaseTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleExceptionLoggerEventListenerTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider onConsoleErrorDataProvider
     *
     * @param string $commandName
     * @param InputDefinition $inputDefinition
     * @param array $commandParameters
     * @param string $expectedLogMessage
     * @param array $expectedLogContextArgs
     * @param array $expectedLogContextOptions
     */
    public function testOnConsoleError(
        string $commandName,
        InputDefinition $inputDefinition,
        array $commandParameters,
        string $expectedLogMessage,
        array $expectedLogContextArgs,
        array $expectedLogContextOptions
    ) {
        $input = new ArrayInput($commandParameters, $inputDefinition);

        /* @var OutputInterface|MockInterface $output */
        $output = \Mockery::mock(OutputInterface::class);

        /* @var \Exception|MockInterface $error */
        $error = \Mockery::mock(\Exception::class);

        /* @var Command|MockInterface $command */
        $command = \Mockery::mock(Command::class);
        $command
            ->shouldReceive('getName')
            ->andReturn($commandName);

        /* @var LoggerInterface|MockInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->withArgs(function (
                string $message,
                array $context
            ) use (
                $expectedLogMessage,
                $expectedLogContextArgs,
                $expectedLogContextOptions
            ) {
                $this->assertEquals($expectedLogMessage, $message);

                $this->assertArrayHasKey('args', $context);
                $this->assertEquals($expectedLogContextArgs, $context['args']);

                $this->assertArrayHasKey('options', $context);
                $this->assertEquals($expectedLogContextOptions, $context['options']);

                $this->assertArrayHasKey('trace', $context);
                $this->assertNotEmpty($context['trace']);

                return true;
            });

        $event = new ConsoleErrorEvent($input, $output, $error, $command);
        $this->assertFalse($event->isPropagationStopped());

        $eventListener = new ConsoleExceptionLoggerEventListener($logger);

        $returnValue = $eventListener->onConsoleError($event);

        $this->assertNull($returnValue);
        $this->assertTrue($event->isPropagationStopped());
    }

    public function onConsoleErrorDataProvider(): array
    {
        return [
            'no args, no options' => [
                'commandName' => 'foo:bar',
                'inputDefintiion' => $this->createInputDefinition([], []),
                'commandParameters' => [],
                'expectedLogMessage' => 'foo:bar',
                'expectedLogContextArgs' => [],
                'expectedLogContextOptions' => [],
            ],
            'has args, has options, options not set' => [
                'commandName' => 'foobar:command',
                'inputDefintiion' => $this->createInputDefinition(['arg1', 'arg2'], ['opt1', 'opt2']),
                'commandParameters' => [
                    'arg1' => 'foo',
                    'arg2' => 'bar',
                ],
                'expectedLogMessage' => 'foobar:command',
                'expectedLogContextArgs' => [
                    'arg1' => 'foo',
                    'arg2' => 'bar',
                ],
                'expectedLogContextOptions' => [
                    'opt1' => false,
                    'opt2' => false,
                ],
            ],
            'has args, has options, options set' => [
                'commandName' => 'foobar:command',
                'inputDefintiion' => $this->createInputDefinition(['arg1', 'arg2'], ['opt1', 'opt2']),
                'commandParameters' => [
                    'arg1' => 'foo',
                    'arg2' => 'bar',
                    '--opt1' => true,
                ],
                'expectedLogMessage' => 'foobar:command',
                'expectedLogContextArgs' => [
                    'arg1' => 'foo',
                    'arg2' => 'bar',
                ],
                'expectedLogContextOptions' => [
                    'opt1' => true,
                    'opt2' => false,
                ],
            ],
        ];
    }

    private function createInputDefinition(array $argumentNames, array $optionNames): InputDefinition
    {
        $inputDefinition = new InputDefinition();

        foreach ($argumentNames as $argumentName) {
            $inputDefinition->addArgument(new InputArgument($argumentName));
        }

        foreach ($optionNames as $optionName) {
            $inputDefinition->addOption(new InputOption($optionName));
        }

        return $inputDefinition;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
