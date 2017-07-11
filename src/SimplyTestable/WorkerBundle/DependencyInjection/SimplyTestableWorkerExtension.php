<?php

namespace SimplyTestable\WorkerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SimplyTestableWorkerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $fileLocator = new FileLocator([
            __DIR__.'/../Resources/config',
        ]);

        $loader = new Loader\YamlFileLoader($container, $fileLocator);
        $loader->load('services.yml');

        $curlOptionsData = Yaml::parse(file_get_contents($fileLocator->locate('curl_options.yml')));
        $container->setParameter('curl_options', $curlOptionsData);
    }
}
