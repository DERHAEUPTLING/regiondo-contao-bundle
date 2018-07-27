<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\DependencyInjection;

use Derhaeuptling\RegiondoBundle\ClientFactory;
use Derhaeuptling\RegiondoBundle\Synchronizer;
use Derhaeuptling\RegiondoBundle\WidgetManager;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DerhaeuptlingRegiondoExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('listener.yml');
        $loader->load('services.yml');

        $this->addClientFactory($mergedConfig, $container);
        $this->addWidgetManager($mergedConfig, $container);
        $this->addSynchronizer($mergedConfig, $container);
    }

    /**
     * Add the client factory.
     *
     * @param array            $mergedConfig
     * @param ContainerBuilder $container
     */
    private function addClientFactory(array $mergedConfig, ContainerBuilder $container): void
    {
        $factory = new Definition(ClientFactory::class, [$mergedConfig['public_key'], $mergedConfig['secure_key'], $mergedConfig['sandbox']]);

        // Set the cache provider
        if ($mergedConfig['cache_provider']) {
            $factory->addMethodCall('setCacheProvider', [new Reference($mergedConfig['cache_provider'])]);
        }

        // Set the logger if debug is disabled
        if (!$mergedConfig['debug']) {
            $factory->addMethodCall('setLogger', [new Reference('logger')]);
            $factory->addTag('monolog.logger', ['channel' => 'regiondo']);
        }

        $container->setDefinition(ClientFactory::class, $factory);
    }

    /**
     * Add the widget manager.
     *
     * @param array            $mergedConfig
     * @param ContainerBuilder $container
     */
    private function addWidgetManager(array $mergedConfig, ContainerBuilder $container): void
    {
        $manager = new Definition(WidgetManager::class, [$mergedConfig['vendor'], $mergedConfig['sandbox']]);

        if (isset($mergedConfig['widget_base_url']) && null !== $mergedConfig['widget_base_url']) {
            $manager->addMethodCall('setWidgetBaseUrl', [$mergedConfig['widget_base_url']]);
        }

        $container->setDefinition(WidgetManager::class, $manager);
    }

    /**
     * Add the synchronizer.
     *
     * @param array            $mergedConfig
     * @param ContainerBuilder $container
     */
    private function addSynchronizer(array $mergedConfig, ContainerBuilder $container): void
    {
        $synchronizer = $container->getDefinition(Synchronizer::class);

        $arguments = $synchronizer->getArguments();
        $arguments[] = $mergedConfig['assets_folder'];

        $synchronizer->setArguments($arguments);
    }
}
