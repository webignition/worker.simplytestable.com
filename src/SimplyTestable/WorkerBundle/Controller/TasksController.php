<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactoryService as ResqueJobFactoryService;

class TasksController extends BaseController {
    
    public function notifyAction() {
        if ($this->getResqueQueueService()->isEmpty('tasks-request')) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'tasks-request',
                    ['limit' => $this->container->getParameter('worker_process_count')]
                )
            );
        }

        return $this->sendResponse();
    }


    /**
     *
     * @return ResqueQueueService
     */
    protected function getResqueQueueService() {
        return $this->container->get('simplytestable.services.resque.queueservice');
    }


    /**
     *
     * @return ResqueJobFactoryService
     */
    protected function getResqueJobFactoryService() {
        return $this->container->get('simplytestable.services.resque.jobFactoryService');
    }
}
