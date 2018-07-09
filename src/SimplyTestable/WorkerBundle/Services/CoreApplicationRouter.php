<?php

namespace SimplyTestable\WorkerBundle\Services;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class CoreApplicationRouter
{
    const BUNDLE_CONFIG_PATH = '@SimplyTestableWorkerBundle/Resources/config';
    const ROUTING_RESOURCE = 'coreapplicationrouting.yml';
    const URL_PLACEHOLDER = '{{url}}';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $encodedUrlPlaceholder = null;

    /**
     * @param string $baseUrl
     * @param string $cacheDirectory
     * @param ResourceLocator $resourceLocator
     */
    public function __construct(
        $baseUrl,
        $cacheDirectory,
        ResourceLocator $resourceLocator
    ) {
        $locator = new FileLocator($resourceLocator->locate(self::BUNDLE_CONFIG_PATH));
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
}
