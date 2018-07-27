<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Derhaeuptling\RegiondoBundle\Synchronizer;

class ContentListener
{
    /**
     * @var Synchronizer
     */
    private $synchronizer;

    /**
     * ContentListener constructor.
     *
     * @param Synchronizer $synchronizer
     */
    public function __construct(Synchronizer $synchronizer)
    {
        $this->synchronizer = $synchronizer;
    }

    /**
     * On load callback.
     */
    public function onLoadCallback(): void
    {
        // Display the edit warning message
        if ('calendar' === Input::get('do')
            && null !== ($event = CalendarEventsModel::findByPk(CURRENT_ID))
            && null !== ($calendar = CalendarModel::findByPk($event->pid))
            && $calendar->regiondo_enable
        ) {
            System::loadLanguageFile('tl_calendar_events');
            Message::addInfo($GLOBALS['TL_LANG']['tl_calendar_events']['regiondo_messageEditWarning']);
        }
    }

    /**
     * On get the calendar options callback.
     *
     * @return array
     */
    public function onGetCalendarOptionsCallback(): array
    {
        $options = [];

        if (null !== ($calendars = CalendarModel::findBy('regiondo_enable', 1, ['order' => 'title']))) {
            /** @var CalendarModel $calendar */
            foreach ($calendars as $calendar) {
                // Skip products without events selected
                if (0 === \count(StringUtil::deserialize($calendar->regiondo_products, true))) {
                    continue;
                }

                $options[$calendar->id] = $calendar->title;
            }
        }

        return $options;
    }

    /**
     * On synchronize reviews load callback.
     *
     * @return int
     */
    public function onSyncReviewsLoadCallback(): int
    {
        return 1;
    }

    /**
     * On synchronize reviews save callback.
     *
     * @param mixed         $value
     * @param DataContainer $dc
     */
    public function onSyncReviewsSaveCallback($value, DataContainer $dc)
    {
        if ($value) {
            $this->synchronizer->synchronizeReviews($dc->id);
        }

        return null;
    }
}
