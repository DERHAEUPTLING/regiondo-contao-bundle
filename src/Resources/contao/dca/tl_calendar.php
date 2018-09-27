<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Derhaeuptling\RegiondoBundle\EventListener\CalendarListener;

/*
 * Global configuration
 */
$GLOBALS['TL_DCA']['tl_calendar']['config']['onload_callback'][] = [CalendarListener::class, 'onLoadCallback'];
$GLOBALS['TL_DCA']['tl_calendar']['config']['ondelete_callback'][] = [CalendarListener::class, 'onDeleteCallback'];

if (($index = array_search(['tl_calendar', 'checkPermission'], $GLOBALS['TL_DCA']['tl_calendar']['config']['onload_callback'], true)) !== false) {
    $GLOBALS['TL_DCA']['tl_calendar']['config']['onload_callback'][$index] = [CalendarListener::class, 'checkPermission'];
}

/*
 * Adjust the list configuration
 */
$GLOBALS['TL_DCA']['tl_calendar']['list']['label']['fields'] = ['title', 'regiondo_enable', 'regiondo_products', 'regiondo_lastSync'];
$GLOBALS['TL_DCA']['tl_calendar']['list']['label']['showColumns'] = true;
$GLOBALS['TL_DCA']['tl_calendar']['list']['label']['label_callback'] = [CalendarListener::class, 'onLabelCallback'];

array_insert($GLOBALS['TL_DCA']['tl_calendar']['list']['global_operations'], 0, [
    'regiondo_sync' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar']['regiondo_syncAll'],
        'href' => 'act=regiondo_sync_all',
        'icon' => 'sync.svg',
        'attributes' => 'onclick="Backend.getScrollOffset();AjaxRequest.displayBox(Contao.lang.loading + \' …\')"',
        'button_callback' => [CalendarListener::class, 'onSyncAllButtonCallback'],
    ],
]);

$GLOBALS['TL_DCA']['tl_calendar']['list']['operations']['regiondo_sync'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar']['regiondo_sync'],
    'href' => 'act=regiondo_sync',
    'icon' => 'sync.svg',
    'attributes' => 'onclick="AjaxRequest.displayBox(Contao.lang.loading + \' …\')"',
    'button_callback' => [CalendarListener::class, 'onSyncButtonCallback'],
];

/*
 * Extend palettes
 */
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'regiondo_enable';
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['regiondo_enable'] = 'regiondo_products,regiondo_lastSync';

PaletteManipulator::create()
    ->addLegend('regiondo_legend', 'title_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('regiondo_enable', 'regiondo_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar');

/*
 * Add fields
 */
$GLOBALS['TL_DCA']['tl_calendar']['fields']['regiondo_enable'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar']['regiondo_enable'],
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'boolean', 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['regiondo_products'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar']['regiondo_products'],
    'exclude' => true,
    'filter' => true,
    'inputType' => 'select',
    'options_callback' => [\Derhaeuptling\RegiondoBundle\EventListener\ProductListener::class, 'onGetProductsOptionsCallback'],
    'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['regiondo_lastSync'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar']['regiondo_lastSync'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['readonly' => true, 'rgxp' => 'datim', 'tl_class' => 'w50'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false],
];
