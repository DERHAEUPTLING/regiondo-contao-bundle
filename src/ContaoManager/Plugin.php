<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\ContaoManager;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Derhaeuptling\RegiondoBundle\DerhaeuptlingRegiondoBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(DerhaeuptlingRegiondoBundle::class)->setLoadAfter([
                ContaoCalendarBundle::class,
                ContaoCoreBundle::class,
                MonologBundle::class,
            ]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        /** @var \Symfony\Component\Routing\RouteCollection $collection */
        $collection = $resolver
            ->resolve(null, 'annotation')
            ->load('@DerhaeuptlingRegiondoBundle/Controller')
        ;

        return $collection;
    }
}
