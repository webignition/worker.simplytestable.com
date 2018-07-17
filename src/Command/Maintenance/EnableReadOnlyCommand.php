<?php
namespace App\Command\Maintenance;

use App\Services\WorkerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableReadOnlyCommand extends Command
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
            ->setName('simplytestable:maintenance:enable-read-only')
            ->setDescription('Enable read-only mode')
            ->setHelp('Enable read-only mode');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->workerService->setReadOnly();
        $output->writeln('Set state to maintenance-read-only');

        return 0;
    }
}
