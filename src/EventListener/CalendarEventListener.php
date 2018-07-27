<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\EventListener;

use Contao\Backend;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Derhaeuptling\RegiondoBundle\Entity\Event;
use Derhaeuptling\RegiondoBundle\Exception\SynchronizerException;
use Derhaeuptling\RegiondoBundle\Synchronizer;

class CalendarEventListener
{
    /**
     * @var Synchronizer
     */
    private $synchronizer;

    /**
     * CalendarEventListener constructor.
     *
     * @param Synchronizer $synchronizer
     */
    public function __construct(Synchronizer $synchronizer)
    {
        $this->synchronizer = $synchronizer;
    }

    /**
     * On data container load callback.
     *
     * @param DataContainer $dc
     */
    public function onLoadCallback(DataContainer $dc = null): void
    {
        // Synchronize the calendar
        if ('regiondo_sync' === Input::get('act')) {
            System::loadLanguageFile('tl_calendar');

            try {
                $this->synchronizer->synchronizeProducts();
                $stats = $this->synchronizer->synchronizeCalendar(CURRENT_ID);
            } catch (SynchronizerException $e) {
                $stats = null;
                Message::addError(\sprintf($GLOBALS['TL_LANG']['tl_calendar']['regiondo_syncError'], CalendarModel::findByPk(CURRENT_ID)->title, $e->getMessage()));
            }

            if (null !== $stats) {
                Message::addConfirmation(\sprintf($GLOBALS['TL_LANG']['tl_calendar']['regiondo_syncConfirm'], $stats['calendar'], $stats['created'], $stats['updated'], $stats['deleted']));
            }

            Controller::redirect(System::getReferer());
        }

        // Disable certain fields if the event has been synced from Regiondo
        if (null !== $dc && $dc->id && null !== ($event = CalendarEventsModel::findByPk($dc->id)) && $event->regiondo_product > 0) {
            $GLOBALS['TL_DCA'][$dc->table]['fields']['author']['eval']['mandatory'] = false;

            foreach (\array_keys($this->synchronizer->getFieldsMapper()) as $field) {
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['mandatory'] = false;
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['disabled'] = true;
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['readonly'] = true;
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['doNotSaveEmpty'] = true;

                // Turn off the teaser RTE as it cannot be just disabled
                if ('teaser' === $field) {
                    $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['rte'] = false;
                }
            }
        }

        // Display the edit warning message
        if (null !== ($calendar = CalendarModel::findByPk(CURRENT_ID)) && $calendar->regiondo_enable) {
            Message::addInfo($GLOBALS['TL_LANG']['tl_calendar_events']['regiondo_messageEditWarning']);
        }
    }

    /**
     * On synchronise button callback.
     *
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $cssClass
     * @param string $attributes
     *
     * @return string
     */
    public function onSyncButtonCallback(string $href, string $label, string $title, string $cssClass, string $attributes): string
    {
        if (null === ($calendar = CalendarModel::findByPk(CURRENT_ID))
            || !$calendar->regiondo_enable
            || 0 === \count(StringUtil::deserialize($calendar->regiondo_products, true))
        ) {
            return '';
        }

        return '<a href="'.Backend::addToUrl($href).'" class="'.$cssClass.'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.$label.'</a> ';
    }

    /**
     * On operation button callback.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function onOperationButtonCallback(array $row, ?string $href, string $label, string $title, ?string $icon, string $attributes): string
    {
        if (null !== ($calendar = CalendarModel::findByPk($row['pid'])) && $calendar->regiondo_enable && $row['regiondo_product']) {
            return Image::getHtml(\preg_replace('/\.svg/i', '_.svg', $icon)).' ';
        }

        return '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }
}
