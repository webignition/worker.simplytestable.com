<?php
namespace SimplyTestable\WorkerBundle\Command\Memcache\HttpCache;

use SimplyTestable\WorkerBundle\Services\MemcacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Cache\MemcacheCache;

class ClearCommand extends Command
{
    /**
     * @var MemcacheService
     */
    private $memcacheService;

    /**
     * @param MemcacheService $memcacheService
     * @param string|null $name
     */
    public function __construct(MemcacheService $memcacheService, $name = null)
    {
        parent::__construct($name);
        $this->memcacheService = $memcacheService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:memcache:httpcache:clear')
            ->setDescription('Clear memcache http cache')
            ->setHelp('Clear memcache http cache');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $memcacheCache = new MemcacheCache();
        $memcacheCache->setMemcache($this->memcacheService->get());

        return ($memcacheCache->deleteAll()) ? 0 : 1;
    }
}
