<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class RequestIfEmptyCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const QUEUE_NAME = 'tasks-request';

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var ResqueJobFactory
     */
    private $resqueJobFactory;

    /**
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param string|null $name
     */
    public function __construct(
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        $name = null
    ) {
        parent::__construct($name);

        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:tasks:requestifempty')
            ->setDescription('Pop a resque tasks-request job in the queue if the queue is empty')
            ->setHelp('Request tasks to be assigned by the core application');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->resqueQueueService->isEmpty(self::QUEUE_NAME)) {
            $this->resqueQueueService->enqueue(
                $this->resqueJobFactory->create(
                    self::QUEUE_NAME
                )
            );
        }

        return self::RETURN_CODE_OK;
    }
}
