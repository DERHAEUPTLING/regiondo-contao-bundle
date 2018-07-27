<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Haste\Util\Format;

class DataFormatter
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * DataFormatter constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Format the date.
     *
     * @param int $timestamp
     *
     * @return string
     */
    public function formatDate(int $timestamp): string
    {
        $this->framework->initialize();

        /** @var Format $adapter */
        $adapter = $this->framework->getAdapter(Format::class);

        return $adapter->datim($timestamp);
    }

    /**
     * Format the option label.
     *
     * @param array $option
     *
     * @return string
     */
    public function formatOptionLabel(array $option): string
    {
        $this->framework->initialize();

        return \sprintf($GLOBALS['TL_LANG']['MSC']['regiondo.format.optionLabel'], $option['name'], $this->formatPrice($option['regiondo_price']));
    }

    /**
     * Format the price.
     *
     * @param int|float|string $price
     *
     * @return string
     */
    public function formatPrice($price): string
    {
        $this->framework->initialize();

        return \sprintf($GLOBALS['TL_LANG']['MSC']['regiondo.format.price'], \number_format((float) $price, 2));
    }
}
