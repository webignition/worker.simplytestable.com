<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Integration\Command\Task;

use App\Command\Task\PerformCommand;
use App\Entity\CachedResource;
use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Resque\Job\TaskReportCompletionJob;
use App\Tests\Factory\CssValidatorFixtureFactory;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Services\TestTaskFactory;
use App\Services\Resque\QueueService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/Task/PerformCommand
 */
class PerformCommandTest extends AbstractBaseTestCase
{
    /**
     * @var PerformCommand
     */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(PerformCommand::class);
    }

    /**
     * @dataProvider runDataProvider
     */
    public function testRun(
        callable $setUp,
        array $httpFixtures,
        array $taskValues,
        string $primarySourceContent,
        array $expectedDecodedOutput
    ) {
        $setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);

        $httpMockHandler = self::$container->get(HttpMockHandler::class);
        $httpMockHandler->appendFixtures($httpFixtures);

        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $task = $testTaskFactory->create($taskValues);
        $testTaskFactory->addPrimaryCachedResourceSourceToTask($task, $primarySourceContent);

        $sources = $task->getSources();
        $primarySource = $sources[$task->getUrl()];
        $primarySourceRequestHash = $primarySource->getValue();

        $this->assertNotNull($entityManager->find(CachedResource::class, $primarySourceRequestHash));

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(Task::STATE_COMPLETED, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals(0, $output->getErrorCount());
        $this->assertEquals(0, $output->getWarningCount());
        $this->assertEquals('application/json', $output->getContentType());
        $this->assertEquals($expectedDecodedOutput, json_decode($output->getOutput(), true));

        $this->assertTrue(self::$container->get(QueueService::class)->contains(
            TaskReportCompletionJob::QUEUE_NAME,
            [
                'id' => $task->getId()
            ]
        ));

        $this->assertNull($entityManager->find(CachedResource::class, $primarySourceRequestHash));
    }

    public function runDataProvider(): array
    {
        return [
            'html validation' => [
                'setUp' => function () {
                    HtmlValidatorFixtureFactory::set(
                        HtmlValidatorFixtureFactory::load('0-errors')
                    );
                },
                'httpFixtures' => [],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                ]),
                'primarySourceContent' => '<!doctype html>',
                'expectedDecodedOutput' => [
                    'messages' => [],
                ],
            ],
            'css validation' => [
                'setUp' => function () {
                    CssValidatorFixtureFactory::set(
                        CssValidatorFixtureFactory::load('no-messages')
                    );
                },
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'primarySourceContent' => HtmlDocumentFactory::load('empty-body-single-css-link'),
                'expectedDecodedOutput' => [],
            ],
            'link integrity' => [
                'setUp' => function () {
                    CssValidatorFixtureFactory::set(
                        CssValidatorFixtureFactory::load('no-messages')
                    );
                },
                'httpFixtures' => [
                    new Response(200),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_LINK_INTEGRITY,
                ]),
                'primarySourceContent' =>
                    '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
                'expectedDecodedOutput' => [
                    [
                        'context' => '<a href="/foo"></a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'url discovery' => [
                'setUp' => function () {
                },
                'httpFixtures' => [],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_URL_DISCOVERY,
                ]),
                'primarySourceContent' =>
                    '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
                'expectedDecodedOutput' => [
                    'http://example.com/foo',
                ],
            ],
        ];
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
