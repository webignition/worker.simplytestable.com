<?php
namespace SimplyTestable\AppBundle\Command\Maintenance;

use SimplyTestable\AppBundle\Services\WorkerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DisableReadOnlyCommand extends Command
{
    /**
     * @var WorkerService
     */
    private $workerService;

    /**
     * @param WorkerService $workerService
     * @param string|null $name
     */
    public function __construct(WorkerService $workerService, $name = null)
    {
        parent::__construct($name);
        $this->workerService = $workerService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:disable-read-only')
            ->setDescription('Disable read-only mode')
            ->setHelp('Disable read-only mode');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->workerService->setActive();
        $output->writeln('Set state to active');

        return 0;
    }
}
