<?php
namespace SimplyTestable\WorkerBundle\Command\Memcached\HttpCache;

use SimplyTestable\WorkerBundle\Services\HttpClientService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends Command
{
    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @param HttpClientService $httpClientService
     *
     * @param string|null $name
     */
    public function __construct(HttpClientService $httpClientService, $name = null)
    {
        parent::__construct($name);
        $this->httpClientService = $httpClientService;
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
        return ($this->httpClientService->getMemcachedCache()->deleteAll()) ? 0 : 1;
    }
}
