<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\ContentElement;

use Contao\CalendarEventsModel;
use Contao\System;
use Derhaeuptling\RegiondoBundle\Entity\Event;
use Derhaeuptling\RegiondoBundle\EventHelper;
use Derhaeuptling\RegiondoBundle\WidgetManager;

class EventBookingIframeElement extends EventBookingElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_regiondo_event_booking_iframe';

    /**
     * Generate the Regiondo event URL hash.
     *
     * @param string|int $eventId
     * @param boolean    $addTime
     *
     * @return null|string
     */
    public static function generateUrlHash($eventId, bool $addTime = true): ?string
    {
        if (null === ($model = CalendarEventsModel::findByPk($eventId))) {
            return null;
        }

        $date = Event::createFromModel($model)->getDate();

        if (!$addTime) {
            return \sprintf('regiondo-%s', $date->format('Y-m-d'));
        }

        return \sprintf('regiondo-%s-%s', $date->format('Y-m-d'), $date->format('H:i'));
    }

    /**
     * Generate the content element.
     */
    protected function compile(): void
    {
        $products = [];
        $container = System::getContainer();
        $iframeBaseUrl = $container->get(WidgetManager::class)->getIframeUrl($GLOBALS['objPage']->language);
        $scriptUrl = $container->get(WidgetManager::class)->getScriptUrl($GLOBALS['objPage']->language).'/regiondo-booking.js';

        // Generate the products
        if (null !== ($events = $container->get(EventHelper::class)->getEvents($this->calendarId))) {
            /** @var CalendarEventsModel $model */
            foreach ($events as $model) {
                $event = Event::createFromModel($model);

                // Create the products entry if it does not exist yet
                if (!isset($products[$event->getProductId()])) {
                    $data = \json_decode($model->regiondo_data, true);

                    $products[$event->getProductId()] = [
                        'id' => $event->getProductId(),
                        'vendor' => $data['product_supplier_id'],
                        'event' => $model->row(),
                        'iframe' => [
                            'title' => $model->title,
                            'url' => \sprintf('%s/bookingwidget/vendor/%s/id/%s', $iframeBaseUrl, $data['product_supplier_id'], $event->getProductId()),
                            'width' => $this->regiondo_iframeWidth,
                            'script' => $scriptUrl,
                        ],
                        'jsonLd' => [],
                    ];
                }

                $products[$event->getProductId()]['jsonLd'][] = $this->generateJsonLinkedData($model);
            }
        }

        $this->Template->products = $products;
        $this->Template->iframeBaseUrl = $iframeBaseUrl;
        $this->Template->scriptUrl = $scriptUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateJsonLinkedData(CalendarEventsModel $model): array
    {
        $data = parent::generateJsonLinkedData($model);
        $urlHash = '#'.static::generateUrlHash($model->id);

        // Append the hash to the URLs
        $data['url'] .= $urlHash;
        $data['offers']['url'] .= $urlHash;

        return $data;
    }
}
