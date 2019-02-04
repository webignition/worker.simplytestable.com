<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Integration\Command\Task;

use App\Command\Task\PrepareCommand;
use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Services\TestTaskFactory;
use App\Services\Resque\QueueService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/Task/PrepareCommand
 */
class PrepareCommandTest extends AbstractBaseTestCase
{
    /**
     * @var PrepareCommand
     */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(PrepareCommand::class);
    }

    /**
     * @dataProvider runDataProvider
     */
    public function testRun(array $httpFixtures, array $taskValues, string $expectedPrimarySourceBody)
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $httpMockHandler = self::$container->get(HttpMockHandler::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $httpMockHandler->appendFixtures($httpFixtures);

        $task = $testTaskFactory->create($taskValues);

        $this->assertEquals(Task::STATE_QUEUED, $task->getState());

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(Task::STATE_PREPARED, $task->getState());

        $sources = $task->getSources();
        $this->assertNotEmpty($sources);

        $primarySource = $sources[$task->getUrl()] ?? null;
        $this->assertInstanceOf(Source::class, $primarySource);
        $this->assertEquals($task->getUrl(), $primarySource->getUrl());
        $this->assertTrue($primarySource->isCachedResource());

        /* @var CachedResource $cachedResource */
        $cachedResource = $entityManager->find(CachedResource::class, $primarySource->getValue());
        $this->assertInstanceOf(CachedResource::class, $cachedResource);
        $this->assertEquals($task->getUrl(), $cachedResource->getUrl());
        $this->assertEquals('text/html', $cachedResource->getContentType());
        $this->assertEquals($expectedPrimarySourceBody, stream_get_contents($cachedResource->getBody()));

        $this->assertTrue(self::$container->get(QueueService::class)->contains(
            'task-perform',
            [
                'id' => $task->getId()
            ]
        ));
    }

    public function runDataProvider(): array
    {
        return [
            'html validation' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                ]),
                'expectedPrimarySourceBody' => '<doctype html>',
            ],
            'css validation, no stylesheets' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'expectedPrimarySourceBody' => '<doctype html>',
            ],
            'css validation, has stylesheet' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css'], 'html {}'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'expectedPrimarySourceBody' => HtmlDocumentFactory::load('empty-body-single-css-link'),
            ],
            'link integrity' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_LINK_INTEGRITY,
                ]),
                'expectedPrimarySourceBody' => '<doctype html>',
            ],
            'url discovery' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_URL_DISCOVERY,
                ]),
                'expectedPrimarySourceBody' => '<doctype html>',
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
