<?php

namespace App\Command\HttpCache;

use Doctrine\Common\Cache\MemcachedCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends Command
{
    private $cache;

    public function __construct(MemcachedCache $memcachedCache, ?string $name = null)
    {
        parent::__construct($name);

        $this->cache = $memcachedCache;
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
        return (int) !$this->cache->deleteAll();
    }
}
