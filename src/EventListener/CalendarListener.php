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
use Contao\CalendarModel;
use Contao\Config;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Date;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Derhaeuptling\RegiondoBundle\Exception\SynchronizerException;
use Derhaeuptling\RegiondoBundle\Synchronizer;
use Doctrine\DBAL\Connection;

class CalendarListener
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var Synchronizer
     */
    private $synchronizer;

    /**
     * CalendarListener constructor.
     *
     * @param Connection   $db
     * @param Synchronizer $synchronizer
     */
    public function __construct(Connection $db, Synchronizer $synchronizer)
    {
        $this->db = $db;
        $this->synchronizer = $synchronizer;
    }

    /**
     * On load callback.
     */
    public function onLoadCallback(): void
    {
        // Synchronize all calendars
        if ('regiondo_sync_all' === Input::get('act')) {
            if (null !== ($calendars = CalendarModel::findBy('regiondo_enable', 1))) {
                $executeSync = true;

                // First synchronize the products
                try {
                    $this->synchronizer->synchronizeProducts();
                } catch (SynchronizerException $e) {
                    $executeSync = false;
                    System::loadLanguageFile('tl_regiondo_product');
                    Message::addError($GLOBALS['TL_LANG']['tl_regiondo_product']['syncError']);
                }

                // Synchronize the calendars if the product sync was successful
                /* @var CalendarModel $calendar */
                if ($executeSync) {
                    foreach ($calendars as $calendar) {
                        try {
                            $stats = $this->synchronizer->synchronizeCalendar((int) $calendar->id);
                        } catch (SynchronizerException $e) {
                            $stats = null;
                            Message::addError(\sprintf($GLOBALS['TL_LANG']['tl_calendar']['regiondo_syncError'], $calendar->title, $e->getMessage()));
                        }

                        if (null !== $stats) {
                            Message::addConfirmation(\sprintf($GLOBALS['TL_LANG']['tl_calendar']['regiondo_syncConfirm'], $stats['calendar'], $stats['created'], $stats['updated'], $stats['deleted']));
                        }
                    }
                }
            }

            Controller::redirect(System::getReferer());
        }

        // Synchronize a single calendar
        if ('regiondo_sync' === Input::get('act') && ($id = Input::get('id'))) {
            try {
                $this->synchronizer->synchronizeProducts();
                $stats = $this->synchronizer->synchronizeCalendar($id);
            } catch (SynchronizerException $e) {
                $stats = null;
                Message::addError(\sprintf($GLOBALS['TL_LANG']['tl_calendar']['regiondo_syncError'], CalendarModel::findByPk($id)->title, $e->getMessage()));
            }

            if (null !== $stats) {
                Message::addConfirmation(\sprintf($GLOBALS['TL_LANG']['tl_calendar']['regiondo_syncConfirm'], $stats['calendar'], $stats['created'], $stats['updated'], $stats['deleted']));
            }

            Controller::redirect(System::getReferer());
        }
    }

    /**
     * On delete callback.
     *
     * @param DataContainer $dc
     */
    public function onDeleteCallback(DataContainer $dc): void
    {
        if ($dc->activeRecord->regiondo_enable) {
            try {
                $this->synchronizer->deleteCalendar((int) $dc->id);
            } catch (SynchronizerException $e) {
                Message::addError(\sprintf($GLOBALS['TL_LANG']['tl_calendar']['regiondo_syncError'], $dc->id, $e->getMessage()));
            }
        }
    }

    /**
     * On label callback.
     *
     * @param array         $row
     * @param string        $label
     * @param DataContainer $dc
     * @param array         $args
     *
     * @return array
     */
    public function onLabelCallback(array $row, $label, DataContainer $dc, array $args): array
    {
        if ($row['regiondo_enable']) {
            $productIds = StringUtil::deserialize($row['regiondo_products'], true);

            // Get the product names
            if (\count($productIds) > 0) {
                $names = [];
                $records = $this->db->fetchAll('SELECT name FROM tl_regiondo_product WHERE id IN ('.\implode(',', $productIds).')');

                foreach ($records as $record) {
                    $names[] = $record['name'];
                }

                $args[2] = \implode(', ', $names);
            }
        } else {
            $args[2] = '';
        }

        if ($row['regiondo_lastSync']) {
            $args[3] = Date::parse(Config::get('datimFormat'), $row['regiondo_lastSync']);
        }

        return $args;
    }

    /**
     * On synchronise all button callback.
     *
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $cssClass
     * @param string $attributes
     *
     * @return string
     */
    public function onSyncAllButtonCallback(string $href, string $label, string $title, string $cssClass, string $attributes): string
    {
        if (0 === CalendarModel::countBy('regiondo_enable', 1)) {
            return '';
        }

        return '<a href="'.Backend::addToUrl($href).'" class="'.$cssClass.'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.$label.'</a> ';
    }

    /**
     * On synchronise button callback.
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
    public function onSyncButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        if (!$row['regiondo_enable'] || 0 === \count(StringUtil::deserialize($row['regiondo_products'], true))) {
            return '';
        }

        return '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }
}
