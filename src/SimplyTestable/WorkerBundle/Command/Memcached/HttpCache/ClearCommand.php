<?php
namespace SimplyTestable\WorkerBundle\Command\Memcached\HttpCache;

use SimplyTestable\WorkerBundle\Services\MemcachedService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Cache\MemcachedCache;

class ClearCommand extends Command
{
    /**
     * @var MemcachedService
     */
    private $memcachedService;

    /**
     * @param MemcachedService $memcacheService
     * @param string|null $name
     */
    public function __construct(MemcachedService $memcacheService, $name = null)
    {
        parent::__construct($name);
        $this->memcachedService = $memcacheService;
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
        $memcacheCached = new MemcachedCache();
        $memcacheCached->setMemcached($this->memcachedService->get());

        return ($memcacheCached->deleteAll()) ? 0 : 1;
    }
}
