<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

use Derhaeuptling\RegiondoBundle\EventListener\CalendarEventListener;

/*
 * Global configuration
 */
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['sql']['keys']['regiondo_product'] = 'index';
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['onload_callback'][] = [CalendarEventListener::class, 'onLoadCallback'];

/*
 * Adjust the list configuration
 */
array_insert($GLOBALS['TL_DCA']['tl_calendar_events']['list']['global_operations'], 0, [
    'regiondo_sync' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar']['regiondo_sync'],
        'href' => 'act=regiondo_sync',
        'icon' => 'sync.svg',
        'attributes' => 'onclick="Backend.getScrollOffset();AjaxRequest.displayBox(Contao.lang.loading + \' …\')"',
        'button_callback' => [CalendarEventListener::class, 'onSyncButtonCallback'],
    ],
]);

foreach (['copy', 'cut', 'delete'] as $operation) {
    $GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'][$operation]['button_callback'] = [CalendarEventListener::class, 'onOperationButtonCallback'];
}

/*
 * Add fields
 */
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regiondo_product'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['regiondo_product'],
    'exclude' => true,
    'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regiondo_variationId'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['regiondo_variationId'],
    'exclude' => true,
    'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regiondo_variationName'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['regiondo_variationName'],
    'exclude' => true,
    'sql' => ['type' => 'string', 'length' => 32, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regiondo_lastSync'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['regiondo_lastSync'],
    'exclude' => true,
    'eval' => ['rgxp' => 'datim'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regiondo_timeZone'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['regiondo_timeZone'],
    'exclude' => true,
    'sql' => ['type' => 'string', 'length' => 32, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regiondo_data'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['regiondo_data'],
    'exclude' => true,
    'sql' => ['type' => 'blob', 'notnull' => false],
];
