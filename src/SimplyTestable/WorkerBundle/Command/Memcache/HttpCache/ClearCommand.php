<?php
namespace SimplyTestable\WorkerBundle\Command\Memcache\HttpCache;

use SimplyTestable\WorkerBundle\Services\MemcacheService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\Common\Cache\MemcacheCache;

class ClearCommand extends ContainerAwareCommand
{
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
        $memcacheCache->setMemcache($this->getMemcacheService()->get());

        return ($memcacheCache->deleteAll()) ? 0 : 1;
    }

    /**
     *
     * @return MemcacheService
     */
    private function getMemcacheService()
    {
        return $this->getContainer()->get('simplytestable.services.memcacheservice');
    }
}
