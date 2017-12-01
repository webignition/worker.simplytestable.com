<?php
namespace SimplyTestable\WorkerBundle\Services\Resque;

use ResqueBundle\Resque\Resque;
use ResqueBundle\Resque\Job;
use Psr\Log\LoggerInterface;
use webignition\ResqueJobFactory\ResqueJobFactory;

/**
 * Wrapper for \ResqueBundle\Resque\Resque that handles exceptions
 * when trying to interact with queues.
 *
 * Exceptions generally occur when trying to establish a socket connection to
 * a redis server that does not exist. This can happen as in some environments
 * where the integration with redis is optional.
 *
 */
class QueueService
{
    const QUEUE_KEY = 'queue';

    /**
     * @var Resque
     */
    private $resque;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResqueJobFactory
     */
    private $jobFactory;

    /**
     * @param Resque $resque
     * @param LoggerInterface $logger
     * @param ResqueJobFactory $jobFactory
     */
    public function __construct(
        Resque $resque,
        LoggerInterface $logger,
        ResqueJobFactory $jobFactory
    ) {
        $this->resque = $resque;
        $this->logger = $logger;
        $this->jobFactory = $jobFactory;
    }

    /**
     * @param string $queue_name
     * @param array $args
     *
     * @return boolean
     */
    public function contains($queue_name, $args = [])
    {
        try {
            return !is_null($this->findRedisValue($queue_name, $args));
        } catch (\CredisException $credisException) {
            $this->logger->warning(
                'ResqueQueueService::enqueue: Redis error ['.$credisException->getMessage().']'
            );
        }

        return false;
    }

    /**
     * @param string $queue
     * @param array $args
     *
     * @return string
     */
    private function findRedisValue($queue, $args)
    {
        $queueLength = $this->getQueueLength($queue);

        for ($queueIndex = 0; $queueIndex < $queueLength; $queueIndex++) {
            $jobDetails = json_decode(\Resque::redis()->lindex(self::QUEUE_KEY . ':' . $queue, $queueIndex));

            if ($this->match($jobDetails, $queue, $args)) {
                return json_encode($jobDetails);
            }
        }

        return null;
    }

    /**
     * @param string $jobDetails
     * @param string $queue
     * @param array $args
     *
     * @return boolean
     */
    private function match($jobDetails, $queue, $args)
    {
        if (!isset($jobDetails->class)) {
            return false;
        }

        if ($jobDetails->class != $this->jobFactory->getJobClassName($queue)) {
            return false;
        }

        if (!isset($jobDetails->args)) {
            return false;
        }

        if (!isset($jobDetails->args[0])) {
            return false;
        }

        foreach ($args as $key => $value) {
            if (!isset($jobDetails->args[0]->$key)) {
                return false;
            }

            if ($jobDetails->args[0]->$key != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $queue
     *
     * @return int
     */
    public function getQueueLength($queue)
    {
        return \Resque::redis()->llen(self::QUEUE_KEY . ':' . $queue);
    }


    /**
     * @param Job $job
     * @param bool $trackStatus
     * @throws \CredisException
     * @throws \Exception
     *
     * @return null|\Resque_Job_Status
     */
    public function enqueue(Job $job, $trackStatus = false)
    {
        try {
            return $this->resque->enqueue($job, $trackStatus);
        } catch (\CredisException $credisException) {
            $this->logger->warning('ResqueQueueService::enqueue: Redis error ['.$credisException->getMessage().']');
        }
    }

    /**
     * @param string $queue
     *
     * @return boolean
     */
    public function isEmpty($queue)
    {
        return $this->getQueueLength($queue) == 0;
    }
}
