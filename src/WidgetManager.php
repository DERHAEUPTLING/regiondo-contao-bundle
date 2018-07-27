<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle;

class WidgetManager
{
    /**
     * @var string
     */
    private $vendor;

    /**
     * @var bool
     */
    private $sandbox = false;

    /**
     * @var string
     */
    private $widgetBaseUrl;

    /**
     * WidgetManager constructor.
     *
     * @param string $vendor
     * @param bool   $sandbox
     */
    public function __construct(string $vendor, bool $sandbox)
    {
        $this->vendor = $vendor;
        $this->sandbox = $sandbox;
    }

    /**
     * Set the widget base URL (including the protocol).
     *
     * @param string $widgetBaseUrl
     */
    public function setWidgetBaseUrl(string $widgetBaseUrl): void
    {
        $this->widgetBaseUrl = \rtrim($widgetBaseUrl, '/');
    }

    /**
     * Get the iFrame widget URL.
     *
     * @param string $language
     *
     * @return string
     */
    public function getIframeUrl(string $language): string
    {
        if (null !== $this->widgetBaseUrl) {
            return $this->widgetBaseUrl;
        }

        if ($this->sandbox) {
            return \sprintf('https://%s.regiondo-dev-%s.de', $this->vendor, $language);
        }

        return \sprintf('https://%s.regiondo-%s.de', $this->vendor, $language);
    }

    /**
     * Get the script widget URL.
     *
     * @param string $language
     *
     * @return string
     */
    public function getScriptUrl(string $language): string
    {
        if ($this->sandbox) {
            return \sprintf('https://www.regiondo-dev-%s.de/js/integration', $language);
        }

        return 'https://cdn.regiondo.net/js/integration';
    }
}
