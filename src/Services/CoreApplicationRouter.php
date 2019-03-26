<?php

namespace App\Services;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

class CoreApplicationRouter implements WarmableInterface
{
    const ROUTING_RESOURCE = 'coreapplicationrouting.yml';
    const URL_PLACEHOLDER = '{{url}}';

    /**
     * @var Router
     */
    private $router;

    /**
     * @var string
     */
    private $encodedUrlPlaceholder = null;

    /**
     * @param string $baseUrl
     * @param string $kernelProjectDirectory
     * @param string $cacheDirectory
     */
    public function __construct($baseUrl, $kernelProjectDirectory, $cacheDirectory)
    {
        $locator = new FileLocator($kernelProjectDirectory . '/config/resources');
        $requestContext = new RequestContext();
        $requestContext->fromRequest(Request::createFromGlobals());

        $this->router = new Router(
            new YamlFileLoader($locator),
            self::ROUTING_RESOURCE,
            ['cache_dir' => $cacheDirectory],
            $requestContext
        );

        $this->router->getContext()->setBaseUrl($baseUrl);

        $this->encodedUrlPlaceholder = rawurlencode(self::URL_PLACEHOLDER);
    }

    /**
     * @param string $name
     * @param array $parameters
     *
     * @return string
     *
     * @see UrlGeneratorInterface::generate()
     */
    public function generate($name, $parameters = array())
    {
        if ($name == 'task_complete') {
            $originalUrl = $parameters['url'];
            $parameters['url'] = '{{url}}';

            $url = str_replace(
                $this->encodedUrlPlaceholder,
                urlencode($originalUrl),
                $this->router->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL)
            );
        } else {
            $url = $this->router->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $currentDir = $this->router->getOption('cache_dir');

        $this->router->setOption('cache_dir', $cacheDir);
        $this->router->getMatcher();
        $this->router->getGenerator();

        $this->router->setOption('cache_dir', $currentDir);
    }
}
