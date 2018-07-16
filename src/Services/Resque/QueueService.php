<?php

namespace App\Services\Resque;

use ResqueBundle\Resque\Resque;
use ResqueBundle\Resque\Job;
use Psr\Log\LoggerInterface;

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
     * @param Resque $resque
     * @param LoggerInterface $logger
     */
    public function __construct(
        Resque $resque,
        LoggerInterface $logger
    ) {
        $this->resque = $resque;
        $this->logger = $logger;
    }

    /**
     * @param string $queue
     * @param array $args
     *
     * @return boolean
     */
    public function contains($queue, $args = [])
    {
        try {
            return !is_null($this->findJobInQueue($queue, $args));
        } catch (\CredisException $credisException) {
            $this->logger->warning(
                'ResqueQueueService::contains: Redis error ['.$credisException->getMessage().']'
            );
        }

        return false;
    }

    /**
     * @param Job $job
     * @param bool $trackStatus
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
     * @param array $args
     *
     * @return Job|null
     */
    private function findJobInQueue($queue, $args)
    {
        $jobs = $this->resque->getQueue($queue)->getJobs();

        foreach ($jobs as $job) {
            /* @var $job Job */

            if ($this->match($job, $args)) {
                return $job;
            }
        }

        return null;
    }

    /**
     * @param Job $job
     * @param array $args
     *
     * @return bool
     */
    private function match(Job $job, $args)
    {
        foreach ($args as $key => $value) {
            if (!isset($job->args[$key])) {
                return false;
            }

            if ($job->args[$key] != $value) {
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
     * @param string $queue
     *
     * @return bool
     */
    public function isEmpty($queue)
    {
        return $this->getQueueLength($queue) == 0;
    }
}
