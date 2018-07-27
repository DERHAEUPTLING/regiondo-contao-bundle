<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\System;
use Derhaeuptling\RegiondoBundle\WidgetManager;
use Patchwork\Utf8;

class VoucherElement extends ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_regiondo_voucher';

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        // Generate a backend wildcard
        if (TL_MODE === 'BE') {
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE'][$this->type][0]).' ###';
            $template->title = $this->headline;

            return $template->parse();
        }

        if (!$this->regiondo_voucher) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the content element.
     */
    protected function compile(): void
    {
        $widgetManager = System::getContainer()->get(WidgetManager::class);
        list($vendorId, $voucherId) = \explode('_', $this->regiondo_voucher);

        $this->Template->config = [
            'title' => $this->headline,
            'url' => \sprintf('%s/bookingwidget/vendor/%s/id/%s', $widgetManager->getIframeUrl($GLOBALS['objPage']->language), $vendorId, $voucherId),
            'width' => $this->regiondo_iframeWidth,
            'script' => $widgetManager->getScriptUrl($GLOBALS['objPage']->language).'/regiondo-booking.js',
        ];
    }
}
