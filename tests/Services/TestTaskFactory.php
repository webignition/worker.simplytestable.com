<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Services;

use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\RequestIdentifierFactory;
use App\Services\SourceFactory;
use App\Services\TaskTypeService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Services\TaskService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\WebResource;

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

        if (isset($taskValues['sources'])) {
            foreach ($taskValues['sources'] as $sourceData) {
                $type = $sourceData['type'] ?? Source::TYPE_CACHED_RESOURCE;

                if (Source::TYPE_CACHED_RESOURCE === $type) {
                    $this->addSourceToTask(
                        $task,
                        $sourceData['url'],
                        $sourceData['content'],
                        $sourceData['contentType']
                    );
                } else {
                    $task->addSource(new Source(
                        $sourceData['url'],
                        $sourceData['type'],
                        $sourceData['value'],
                        $sourceData['context'] ?? []
                    ));
                }
            }
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
        $contentType = $contentType ?? new InternetMediaType('text', 'html');

        $this->addSourceToTask($task, $task->getUrl(), $webPageContent, $contentType);
    }

    public function addSourceToTask(
        Task $task,
        string $resourceUrl,
        string $resourceContent,
        InternetMediaTypeInterface $contentType
    ) {
        $requestIdentifer = $this->requestIdentifierFactory->createFromTaskResource($task, $resourceUrl);

        $webResource = WebResource::createFromContent($resourceContent, $contentType);

        $cachedResource = $this->cachedResourceFactory->create(
            (string) $requestIdentifer,
            $resourceUrl,
            $webResource
        );

        $this->cachedResourceManager->persist($cachedResource);

        $source = $this->sourceFactory->fromCachedResource($cachedResource);
        $task->addSource($source);

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }
}
