<?php

namespace SimplyTestable\WorkerBundle\Tests;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
use Doctrine\Common\Cache\MemcacheCache;
use GuzzleHttp\Message\MessageFactory as HttpMessageFactory;
use GuzzleHttp\Message\ResponseInterface as HttpResponse;
use GuzzleHttp\Message\Request as HttpRequest;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {

    const TASK_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\TaskController';
    const TASKS_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\TasksController';
    const VERIFY_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\VerifyController';
    const MAINTENANCE_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\MaintenanceController';

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    protected function getTaskFactory()
    {
        if (is_null($this->taskFactory)) {
            $this->taskFactory = new TaskFactory(
                $this->getTaskService(),
                $this->getTaskTypeService(),
                $this->getStateService(),
                $this->getEntityManager()
            );
        }

        return $this->taskFactory;
    }

    protected function setActiveState() {
        $this->getWorkerService()->activate();
    }

    /**
     *
     * @param string $methodName
     * @param array $postData
     * @return \SimplyTestable\WorkerBundle\Controller\TaskController
     */
    protected function getTaskController($methodName, $postData = array()) {
        return $this->getController(self::TASK_CONTROLLER_NAME, $methodName, $postData);
    }


    /**
     *
     * @param string $methodName
     * @param array $postData
     * @return \SimplyTestable\WorkerBundle\Controller\TasksController
     */
    protected function getTasksController($methodName, $postData = array()) {
        return $this->getController(self::TASKS_CONTROLLER_NAME, $methodName, $postData);
    }


    /**
     *
     * @param string $methodName
     * @param array $postData
     * @return \SimplyTestable\WorkerBundle\Controller\VerifyController
     */
    protected function getVerifyController($methodName, $postData = array()) {
        return $this->getController(self::VERIFY_CONTROLLER_NAME, $methodName, $postData);
    }

    /**
     *
     * @param string $controllerName
     * @param string $methodName
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Controller
     */
    protected function getController($controllerName, $methodName, array $postData = array(), array $queryData = array()) {
        return $this->createController($controllerName, $methodName, $postData, $queryData);
    }

    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\StateService
     */
    protected function getStateService() {
        return $this->container->get('simplytestable.services.stateservice');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\WorkerService
     */
    protected function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\Resque\QueueService
     */
    protected function getResqueQueueService() {
        return $this->container->get('simplytestable.services.resque.queueservice');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\Resque\JobFactoryService
     */
    protected function getResqueJobFactoryService() {
        return $this->container->get('simplytestable.services.resque.jobFactoryService');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\HttpClientService
     */
    protected function getHttpClientService() {
        return $this->container->get('simplytestable.services.httpclientservice');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TaskService
     */
    protected function getTaskService() {
        return $this->container->get('simplytestable.services.taskservice');
    }

    /**
     * @return \SimplyTestable\WorkerBundle\Services\TaskTypeService
     */
    protected function getTaskTypeService()
    {
        return $this->container->get('simplytestable.services.tasktypeservice');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TasksService
     */
    protected function getTasksService() {
        return $this->container->get('simplytestable.services.tasksservice');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\CoreApplicationService
     */
    protected function getCoreApplicationService() {
        return $this->container->get('simplytestable.services.coreapplicationservice');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\MemcacheService
     */
    protected function getMemcacheService() {
        return $this->container->get('simplytestable.services.memcacheservice');
    }


    /**
     *
     * @return \webignition\WebResource\Service\Service
     */
    protected function getWebResourceService() {
        return $this->container->get('simplytestable.services.webresourceservice');
    }

    /**
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager() {
        return $this->container->get('doctrine')->getManager();
    }


    protected function createCompletedTaskOutputForTask(
            Task $task,
            $output = '1',
            $errorCount = 0,
            $warningCount = 0,
            $contentTypeString = 'application/json',
            $result = 'success', // skipped, failed, succeeded,
            $isRetryable = null,
            $isRetryLimitReached = null
    ) {
        $mediaTypeParser = new InternetMediaTypeParser();
        $contentType = $mediaTypeParser->parse($contentTypeString);

        $taskOutput = new TaskOutput();
        $taskOutput->setContentType($contentType);
        $taskOutput->setErrorCount($errorCount);
        $taskOutput->setOutput($output);
        $taskOutput->setState($this->getStateService()->fetch('taskoutput-queued'));
        $taskOutput->setWarningCount($warningCount);

        $taskDriverResponse = new TaskDriverResponse();

        $taskDriverResponse->setTaskOutput($taskOutput);
        $taskDriverResponse->setErrorCount($taskOutput->getErrorCount());
        $taskDriverResponse->setWarningCount($taskOutput->getWarningCount());

        switch ($result) {
            case 'skipped':
                $taskDriverResponse->setHasBeenSkipped();
                break;

            case 'failed':
                $taskDriverResponse->setHasFailed();
                break;

            default:
                $taskDriverResponse->setHasSucceeded();
                break;
        }

        if ($isRetryable) {
            $taskDriverResponse->setIsRetryable(true);
        }

        if ($isRetryLimitReached) {
            $taskDriverResponse->setIsRetryLimitReached(true);
        }

        $this->getTaskService()->complete($task, $taskDriverResponse);
    }


    protected function clearMemcacheHttpCache() {
        $memcacheCache = new MemcacheCache();
        $memcacheCache->setMemcache($this->getMemcacheService()->get());
        $memcacheCache->deleteAll();
    }


    protected function removeAllTasks() {
        $this->removeAllForEntity('SimplyTestable\WorkerBundle\Entity\Task\Task');
    }

    protected function removeAllTestTaskTypes() {
        $taskTypes = $this->getEntityManager()->getRepository('SimplyTestable\WorkerBundle\Entity\Task\Type\Type')->findAll();

        foreach ($taskTypes as $taskType) {
            if (preg_match('/test-/', $taskType->getName())) {
                $this->getEntityManager()->remove($taskType);
                $this->getEntityManager()->flush();
            }
        }
    }

    private function removeAllForEntity($entityName) {
        $entities = $this->getEntityManager()->getRepository($entityName)->findAll();
        if (is_array($entities) && count($entities) > 0) {
            foreach ($entities as $entity) {
                $this->getEntityManager()->remove($entity);
            }

            $this->getEntityManager()->flush();
        }
    }



    protected function setHttpFixtures($fixtures)
    {
        $this->clearMemcacheHttpCache();

        $this->getHttpClientService()->get()->getEmitter()->attach(
            new HttpMockSubscriber($fixtures)
        );
    }


    protected function getHttpFixtures($path) {
        $httpMessages = array();

        $fixturesDirectory = new \DirectoryIterator($path);
        $fixturePaths = array();
        foreach ($fixturesDirectory as $directoryItem) {
            if ($directoryItem->isFile()) {
                $fixturePaths[] = $directoryItem->getPathname();
            }
        }

        sort($fixturePaths);

        foreach ($fixturePaths as $fixturePath) {
            $httpMessages[] = file_get_contents($fixturePath);
        }

        return $this->buildHttpFixtureSet($httpMessages);
    }


    /**
     *
     * @param array $httpMessages
     * @return array
     */
    protected function buildHttpFixtureSet($items) {
        $fixtures = array();

        foreach ($items as $item) {
            switch ($this->getHttpFixtureItemType($item)) {
                case 'httpMessage':
                    $fixtures[] = $this->getHttpResponseFromMessage($item);
                    break;

                case 'curlException':
                    $fixtures[] = $this->getCurlExceptionFromCurlMessage($item);
                    break;

                default:
                    throw new \LogicException();
            }
        }

        return $fixtures;
    }

    private function getHttpFixtureItemType($item) {
        if (substr($item, 0, strlen('HTTP')) == 'HTTP') {
            return 'httpMessage';
        }

        return 'curlException';
    }


    /**
     *
     * @param string $curlMessage
     * @return ConnectException
     */
    private function getCurlExceptionFromCurlMessage($curlMessage) {
        $curlMessageParts = explode(' ', $curlMessage, 2);

        return new ConnectException(
            'cURL error ' . str_replace('CURL/', '', $curlMessageParts[0]) . ': ' . $curlMessageParts[1],
            new HttpRequest('GET', 'http://example.com/')
        );
    }


    protected function assertSystemCurlOptionsAreSetOnAllRequests() {
        foreach ($this->getHttpClientService()->getHistory()->getRequests(true) as $request) {
            foreach ($this->container->getParameter('curl_options') as $curlOption) {
                $expectedValueAsString = $curlOption['value'];

                if (is_string($expectedValueAsString)) {
                    $expectedValueAsString = '"'.$expectedValueAsString.'"';
                }

                if (is_bool($curlOption['value'])) {
                    $expectedValueAsString = ($curlOption['value']) ? 'true' : 'false';
                }

//                $this->assertEquals(
//                    $curlOption['value'],
//                    $request->getCurlOptions()->get(constant($curlOption['name'])),
//                    'Curl option "'.$curlOption['name'].'" not set to ' . $expectedValueAsString . ' for ' .$httpTransaction['request']->getMethod() . ' ' . $httpTransaction['request']->getUrl()
//                );
            }
        }
    }


    /**
     * @param $message
     * @return HttpResponse
     */
    protected function getHttpResponseFromMessage($message) {
        $factory = new HttpMessageFactory();
        return $factory->fromMessage($message);
    }
}
