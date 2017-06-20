<?php
namespace SimplyTestable\WorkerBundle\Services;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
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
     * @var string
     */
    private $baseUrl;

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
     * @param KernelInterface $kernel
     * @param string $cachePath
     */
    public function __construct($baseUrl, KernelInterface $kernel, $cachePath)
    {
        $this->baseUrl = $baseUrl;

        $locator = new FileLocator([$kernel->locateResource(self::BUNDLE_CONFIG_PATH)]);
        $requestContext = new RequestContext();
        $requestContext->fromRequest(Request::createFromGlobals());

        $this->router = new Router(
            new YamlFileLoader($locator),
            self::ROUTING_RESOURCE,
            ['cache_dir' => $cachePath],
            $requestContext
        );

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

            $relativeUrl = str_replace(
                $this->encodedUrlPlaceholder,
                urlencode($originalUrl),
                $this->router->generate($name, $parameters, false)
            );
        } else {
            $relativeUrl = $this->router->generate($name, $parameters, false);
        }

        return $this->baseUrl . $relativeUrl;
    }
}
