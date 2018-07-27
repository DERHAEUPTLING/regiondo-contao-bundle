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
use Derhaeuptling\RegiondoBundle\WidgetManager;
use Patchwork\Utf8;

class CartWidgetModule extends Module
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_regiondo_cart_widget';

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
        $manager = System::getContainer()->get(WidgetManager::class);

        $this->Template->scriptUrl = $manager->getScriptUrl($GLOBALS['objPage']->language);

        // Add the URL with trailing slash (or otherwise the widget won't work)
        $this->Template->url = $manager->getIframeUrl($GLOBALS['objPage']->language).'/';
    }
}
