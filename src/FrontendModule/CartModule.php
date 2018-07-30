<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\FrontendModule;

use Contao\BackendTemplate;
use Contao\Module;
use Contao\System;
use Patchwork\Utf8;

class CartModule extends Module
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_regiondo_cart';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]).' ###';
            $template->title = $this->headline;
            $template->id = $this->id;
            $template->link = $this->name;
            $template->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $template->parse();
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        $this->Template->config = [
            'url' => System::getContainer()->get('router')->generate('_regiondo_cart', [
                'module' => (int) $this->id,
                'page' => (int) $GLOBALS['objPage']->id,
            ]),
        ];
    }
}
