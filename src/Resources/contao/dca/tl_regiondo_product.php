<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

use Derhaeuptling\RegiondoBundle\EventListener\ProductListener;

$GLOBALS['TL_DCA']['tl_regiondo_product'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'closed' => true,
        'notCopyable' => true,
        'onload_callback' => [
            [ProductListener::class, 'onLoadCallback'],
        ],
        'onsubmit_callback' => [
            [ProductListener::class, 'onSubmitCallback'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'product' => 'unique',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['name'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['name'],
            'format' => '%s',
            'label_callback' => [ProductListener::class, 'onLabelCallback'],
        ],
        'global_operations' => [
            'synchronize' => [
                'label' => &$GLOBALS['TL_LANG']['tl_regiondo_product']['synchronize'],
                'href' => 'act=sync',
                'icon' => 'sync.svg',
                'attributes' => 'onclick="AjaxRequest.displayBox(Contao.lang.loading + \' …\')"',
            ],
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_regiondo_product']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_regiondo_product']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_regiondo_product']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '{product_legend},product,lastSync,name,obsolete',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'product' => [
            'label' => &$GLOBALS['TL_LANG']['tl_regiondo_product']['product'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'options_callback' => [ProductListener::class, 'onGetRegiondoProducts'],
            'eval' => ['disabled' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => false, 'default' => 0],
        ],
        'lastSync' => [
            'label' => &$GLOBALS['TL_LANG']['tl_regiondo_product']['lastSync'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'rgxp' => 'datim', 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false],
        ],
        'name' => [
            'label' => &$GLOBALS['TL_LANG']['tl_regiondo_product']['name'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'obsolete' => [
            'label' => &$GLOBALS['TL_LANG']['tl_regiondo_product']['obsolete'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['disabled' => true, 'tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => '0'],
        ],
    ],
];
