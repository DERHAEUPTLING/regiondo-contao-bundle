<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\EventListener;

use Contao\Controller;

class ModuleListener
{
    /**
     * On get cart template options callback.
     *
     * @return array
     */
    public function onGetCartTemplateOptionsCallback(): array
    {
        return Controller::getTemplateGroup('regiondo_cart_');
    }

    /**
     * On get checkout template options callback.
     *
     * @return array
     */
    public function onGetCheckoutTemplateOptionsCallback(): array
    {
        return Controller::getTemplateGroup('regiondo_checkout_');
    }
}
