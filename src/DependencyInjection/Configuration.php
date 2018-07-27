<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('derhaeuptling_regiondo');

        $rootNode
            ->children()
                ->scalarNode('public_key')->isRequired()->end()
                ->scalarNode('secure_key')->isRequired()->end()
                ->scalarNode('assets_folder')->isRequired()->end()
                ->scalarNode('vendor')->isRequired()->end()
                ->scalarNode('cache_provider')->defaultNull()->end()
                ->scalarNode('widget_base_url')->defaultNull()->end()
                ->booleanNode('sandbox')->defaultFalse()->end()
                ->booleanNode('debug')->defaultFalse()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
