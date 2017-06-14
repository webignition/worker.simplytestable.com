<?php

namespace SimplyTestable\WorkerBundle\Controller;

class TasksController extends BaseController
{
    public function notifyAction()
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $jobFactoryService = $this->container->get('simplytestable.services.resque.jobFactoryService');

        if ($resqueQueueService->isEmpty('tasks-request')) {
            $resqueQueueService->enqueue(
                $jobFactoryService->create(
                    'tasks-request',
                    ['limit' => $this->container->getParameter('worker_process_count')]
                )
            );
        }

        return $this->sendResponse();
    }
}
