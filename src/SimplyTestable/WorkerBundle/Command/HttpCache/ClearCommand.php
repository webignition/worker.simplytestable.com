<?php
namespace SimplyTestable\WorkerBundle\Command\HttpCache;

use SimplyTestable\WorkerBundle\Services\HttpCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends Command
{
    /**
     * @var HttpCache
     */
    private $httpCache;

    /**
     * @param HttpCache $httpCache
     *
     * @param string|null $name
     */
    public function __construct(HttpCache $httpCache, $name = null)
    {
        parent::__construct($name);
        $this->httpCache = $httpCache;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:httpcache:clear')
            ->setDescription('Clear memcache http cache')
            ->setHelp('Clear memcache http cache');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->httpCache->clear() ? 0 : 1;
    }
}
