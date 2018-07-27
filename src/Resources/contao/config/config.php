<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

/**
 * Backend modules.
 */
$GLOBALS['BE_MOD']['system']['regiondo_products'] = [
    'tables' => ['tl_regiondo_product'],
];

/*
 * Frontend modules
 */
$GLOBALS['FE_MOD']['regiondo'] = [
    'regiondo_cart_widget' => \Derhaeuptling\RegiondoBundle\FrontendModule\CartWidgetModule::class,
];

/*
 * Content elements
 */
$GLOBALS['TL_CTE']['regiondo'] = [
    'regiondo_event_booking_iframe' => \Derhaeuptling\RegiondoBundle\ContentElement\EventBookingIframeElement::class,
    'regiondo_reviews' => \Derhaeuptling\RegiondoBundle\ContentElement\ReviewsElement::class,
    'regiondo_voucher' => \Derhaeuptling\RegiondoBundle\ContentElement\VoucherElement::class,
];

/*
 * Models
 */
$GLOBALS['TL_MODELS']['tl_regiondo_product'] = \Derhaeuptling\RegiondoBundle\Model\ProductModel::class;
