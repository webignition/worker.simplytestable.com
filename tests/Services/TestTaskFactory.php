<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Services;

use App\Model\Task\TypeInterface;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\RequestIdentifierFactory;
use App\Services\SourceFactory;
use App\Services\TaskTypeService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Services\TaskService;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\WebPage\WebPage;

class TestTaskFactory
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TypeInterface::TYPE_HTML_VALIDATION;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    private static $defaultTaskValues = [
        'url' => self::DEFAULT_TASK_URL,
        'type' => self::DEFAULT_TASK_TYPE,
        'parameters' => self::DEFAULT_TASK_PARAMETERS,
        'state' => self::DEFAULT_TASK_STATE,
    ];

    private $entityManager;
    private $taskService;
    private $taskTypeService;
    private $requestIdentifierFactory;
    private $cachedResourceFactory;
    private $cachedResourceManager;
    private $sourceFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        TaskTypeService $taskTypeService,
        RequestIdentifierFactory $requestIdentifierFactory,
        CachedResourceFactory $cachedResourceFactory,
        CachedResourceManager $cachedResourceManager,
        SourceFactory $sourceFactory
    ) {
        $this->entityManager = $entityManager;
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
        $this->requestIdentifierFactory = $requestIdentifierFactory;
        $this->cachedResourceFactory = $cachedResourceFactory;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->sourceFactory = $sourceFactory;
    }

    public static function createTaskValuesFromDefaults(array $taskValues = []): array
    {
        return array_merge(self::$defaultTaskValues, $taskValues);
    }

    public function create(array $taskValues): Task
    {
        if (!isset($taskValues['parameters'])) {
            $taskValues['parameters'] = '';
        }

        $task = $this->taskService->create(
            $taskValues['url'],
            $this->taskTypeService->get($taskValues['type']),
            $taskValues['parameters']
        );

        if ($taskValues['state'] != self::DEFAULT_TASK_STATE) {
            $task->setState($taskValues['state']);
        }

        if (isset($taskValues['age'])) {
            $task->setStartDateTime(new \DateTime('-' . $taskValues['age']));
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function addPrimaryCachedResourceSourceToTask(
        Task $task,
        string $webPageContent,
        ?InternetMediaTypeInterface $contentType = null
    ) {
        $requestIdentifer = $this->requestIdentifierFactory->createFromTask($task);

        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($webPageContent);

        if (!empty($contentType)) {
            $webPage = $webPage->setContentType($contentType);
        }

        $cachedResource = $this->cachedResourceFactory->create(
            (string) $requestIdentifer,
            $task->getUrl(),
            $webPage
        );

        $this->cachedResourceManager->persist($cachedResource);

        $source = $this->sourceFactory->fromCachedResource($cachedResource);
        $task->addSource($source);

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }
}
