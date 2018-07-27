<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

use Derhaeuptling\RegiondoBundle\EventListener\ContentListener;

/*
 * Adjust global configuration
 */
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [ContentListener::class, 'onLoadCallback'];

/*
 * Add palettes
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['regiondo_event_booking_iframe'] = '{type_legend},type;{include_legend},regiondo_calendar,regiondo_iframeWidth;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['regiondo_reviews'] = '{type_legend},type;{include_legend},regiondo_products,regiondo_reviewsLimit,regiondo_syncReviews;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['regiondo_voucher'] = '{type_legend},type;{include_legend},regiondo_voucher,regiondo_iframeWidth;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';

/*
 * Add fields
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['regiondo_calendar'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['regiondo_calendar'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ContentListener::class, 'onGetCalendarOptionsCallback'],
    'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['regiondo_products'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['regiondo_products'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [\Derhaeuptling\RegiondoBundle\EventListener\ProductListener::class, 'onGetProductsOptionsCallback'],
    'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['regiondo_voucher'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['regiondo_voucher'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [\Derhaeuptling\RegiondoBundle\EventListener\ProductListener::class, 'onGetVoucherOptionsCallback'],
    'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['regiondo_iframeWidth'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['regiondo_iframeWidth'],
    'default' => 338,
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => ['type' => 'smallint', 'unsigned' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['regiondo_reviewsLimit'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['regiondo_reviewsLimit'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['mandatory' => true, 'rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql' => ['type' => 'smallint', 'unsigned' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['regiondo_syncReviews'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['regiondo_syncReviews'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['doNotSaveEmpty' => true, 'tl_class' => 'w50 m12'],
    'load_callback' => [
        [ContentListener::class, 'onSyncReviewsLoadCallback'],
    ],
    'save_callback' => [
        [ContentListener::class, 'onSyncReviewsSaveCallback'],
    ],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['regiondo_reviews'] = [
    'sql' => ['type' => 'blob'],
];
