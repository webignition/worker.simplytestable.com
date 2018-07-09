<?php

namespace SimplyTestable\WorkerBundle\Cache;

use SimplyTestable\WorkerBundle\Services\CoreApplicationRouter;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class CoreApplicationRouterCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var CoreApplicationRouter
     */
    private $coreApplicationRouter;

    /**
     * @param CoreApplicationRouter $coreApplicationRouter
     */
    public function __construct(CoreApplicationRouter $coreApplicationRouter)
    {
        $this->coreApplicationRouter = $coreApplicationRouter;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir)
    {
        $this->coreApplicationRouter->warmUp($cacheDir);
    }
}
