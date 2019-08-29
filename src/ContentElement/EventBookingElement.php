<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @author     Moritz V. <https://github.com/m-vo>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\ContentElement;

use Contao\BackendTemplate;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\ContentElement;
use Contao\Environment;
use Contao\FilesModel;
use Contao\System;
use Derhaeuptling\RegiondoBundle\Entity\Event;
use Derhaeuptling\RegiondoBundle\EventHelper;
use Patchwork\Utf8;

class EventBookingElement extends ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_regiondo_event_booking';

    /**
     * @var int
     */
    protected $calendarId;

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $this->calendarId = (int) $this->regiondo_calendar;

        // Generate a backend wildcard
        if (TL_MODE === 'BE') {
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE'][$this->type][0]).' ###';
            $template->title = $this->headline;

            if (null !== ($calendar = CalendarModel::findByPk($this->calendarId))) {
                $template->id = $calendar->id;
                $template->link = $calendar->title;
            }

            return $template->parse();
        }

        // Return if there is no calendar chosen
        if (!$this->calendarId) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the content element.
     */
    protected function compile(): void
    {
        $products = [];
        $container = System::getContainer();

        // Generate the products and variations
        if (null !== ($events = $container->get(EventHelper::class)->getEvents($this->calendarId))) {
            $pageId = (int) $GLOBALS['objPage']->id;
            $contentId = (int) $this->id;

            /** @var CalendarEventsModel $model */
            foreach ($events as $model) {
                $event = Event::createFromModel($model);
                $eventId = (int) $model->id;

                // Create the products entry if it does not exist yet
                if (!isset($products[$event->getProductId()])) {
                    $products[$event->getProductId()] = [
                        'event' => $model->row(),
                        'jsonLd' => [],
                        'variations' => [],
                        'config' => [
                            'eventId' => $eventId,
                            'url' => $container->get('router')->generate('_regiondo_event_booking', [
                                'event' => $eventId,
                                'content' => $contentId,
                                'page' => $pageId,
                            ]),
                        ],
                    ];
                }

                $products[$event->getProductId()]['jsonLd'][] = $this->generateJsonLinkedData($model);
                $products[$event->getProductId()]['variations'][$event->getVariationId()] = $model->row();
            }
        }

        $this->Template->products = $products;
    }

    /**
     * Generate the JSON linked data.
     *
     * @param CalendarEventsModel $model
     *
     * @return array
     */
    protected function generateJsonLinkedData(CalendarEventsModel $model): array
    {
        $startDate = new \DateTime();
        $startDate->setTimestamp($model->startTime);

        $endDate = new \DateTime();
        $endDate->setTimestamp($model->endTime);

        $originalData = \json_decode($model->regiondo_data, true);
        $option = \reset($originalData['options']);

        $data = [
            '@context' => 'http://www.schema.org',
            '@type' => 'Event',
            'name' => $model->title,
            'url' => Environment::get('uri'),
            'description' => \strip_tags($model->teaser),
            'startDate' => $startDate->format(DATE_ATOM),
            'endDate' => $endDate->format(DATE_ATOM),
            'location' => [
                '@type' => 'Place',
                'name' => $originalData['location_name'],
                'address' => $originalData['location_address'],
            ],
            'performer' => [
                '@type' => 'Organization',
                'name'  => $originalData['provider']
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => \number_format($option['regiondo_price'], 2, '.', ''),
                'priceCurrency' => 'EUR',
                'url' => Environment::get('uri'),
                'availability' => ($option['qty_left'] > 0) ? 'http://schema.org/InStock' : 'http://schema.org/OutOfStock',
                'validFrom' => $originalData['created_at'],
            ],
            
        ];

        // Add aggregate ratings if present
        if ($originalData['reviews_count'] && $originalData['rating_summary']) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingCount' => $originalData['reviews_count'],
                'ratingValue' => $originalData['rating_summary'],
                'bestRating' => 100,
            ];
        }

        // Add the image
        if ($model->addImage && null !== ($image = FilesModel::findByPk($model->singleSRC)) && \is_file(TL_ROOT.'/'.$image->path)) {
            $data['image'] = Environment::get('url').'/'.$image->path;
        }

        return $data;
    }
}
