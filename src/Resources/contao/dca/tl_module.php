<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

use Derhaeuptling\RegiondoBundle\EventListener\ModuleListener;

/*
 * Add palettes
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['regiondo_cart'] = '{title_legend},name,headline,type;{redirect_legend},jumpTo;{template_legend:hide},customTpl,regiondo_cartTemplate;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['regiondo_cart_widget'] = '{title_legend},name,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['regiondo_checkout'] = '{title_legend},name,headline,type;{template_legend:hide},customTpl,regiondo_checkoutTemplate;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

/*
 * Add fields
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['regiondo_cartTemplate'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['regiondo_cartTemplate'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleListener::class, 'onGetCartTemplateOptionsCallback'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['regiondo_checkoutTemplate'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['regiondo_checkoutTemplate'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleListener::class, 'onGetCheckoutTemplateOptionsCallback'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
];
