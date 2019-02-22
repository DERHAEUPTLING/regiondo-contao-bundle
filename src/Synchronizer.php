<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle;

use Contao\CalendarModel;
use Contao\File;
use Contao\FilesModel;
use Contao\Folder;
use Contao\StringUtil;
use Derhaeuptling\RegiondoBundle\Exception\SynchronizerException;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Synchronizer
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $assetsFolder;

    /**
     * Synchronizer constructor.
     *
     * @param ClientFactory   $clientFactory
     * @param Connection      $db
     * @param LoggerInterface $logger
     * @param string          $assetsFolder
     */
    public function __construct(ClientFactory $clientFactory, Connection $db, LoggerInterface $logger, string $assetsFolder = '')
    {
        $this->clientFactory = $clientFactory;
        $this->db = $db;
        $this->logger = $logger;
        $this->assetsFolder = $assetsFolder;
    }

    /**
     * Synchronize the products.
     *
     * @throws SynchronizerException
     *
     * @return array
     */
    public function synchronizeProducts(): array
    {
        $products = $this->clientFactory->create()->getProducts();

        if (null === $products) {
            throw new SynchronizerException('There are no products to synchronize');
        }

        $time = \time();

        $stats = [
            'products' => [],
            'created' => 0,
            'updated' => 0,
        ];

        // Update the products
        foreach ($products as $product) {
            $productId = (int) $product['product_id'];
            $stats['products'][] = $productId;
            $exists = $this->db->fetchColumn('SELECT id FROM tl_regiondo_product WHERE product=?', [$productId]);

            // Create new record
            if (false === $exists) {
                $this->db->insert('tl_regiondo_product', [
                    'tstamp' => $time,
                    'lastSync' => $time,
                    'product' => $productId,
                    'name' => $product['name'],
                ]);

                ++$stats['created'];
            } else {
                // Update the existing record
                $this->db->update('tl_regiondo_product', ['lastSync' => $time, 'obsolete' => 0], ['id' => $exists]);
                ++$stats['updated'];
            }
        }

        // Mark the obsolete records
        $stats['obsolete'] = $this->db->executeUpdate('UPDATE tl_regiondo_product SET obsolete=1 WHERE lastSync<?', [$time]);

        // Flush the cache
        $this->clientFactory->flushCache();

        // Log the sync result
        $this->logger->info('Regiondo products have been synchronized', $stats);

        return $stats;
    }

    /**
     * Synchronize the reviews.
     *
     * @param int $contentElementId
     *
     * @throws SynchronizerException
     *
     * @return array
     */
    public function synchronizeReviews(int $contentElementId): array
    {
        $contentElement = $this->db->fetchAssoc('SELECT * FROM tl_content WHERE id=?', [$contentElementId]);

        if (false === $contentElement) {
            throw new SynchronizerException(\sprintf('Content element ID %s does not exist', $contentElementId));
        }

        $productIds = $this->getProductIds($contentElement['regiondo_products']);

        if (0 === \count($productIds)) {
            throw new SynchronizerException(\sprintf('Content element ID %s has no Regiondo products', $contentElementId));
        }

        $reviews = [];
        $client = $this->clientFactory->create();

        // Get the reviews
        foreach ($productIds as $productId) {
            if (null === ($items = $client->getReviews($productId))) {
                continue;
            }

            $product = $client->getProduct($productId);

            foreach ($items as $item) {
                $item['product'] = $product;
                $reviews[] = $item;
            }
        }

        // Update the content element
        $this->db->update(
            'tl_content',
            ['tstamp' => \time(), 'regiondo_reviews' => \json_encode($reviews)],
            ['id' => $contentElementId]
        );

        $total = \count($reviews);

        // Log the sync result
        $this->logger->info('Regiondo reviews have been synchronized', ['contentElement' => $contentElementId, 'total' => $total]);

        return ['total' => $total];
    }

    /**
     * Synchronize the calendar.
     *
     * @param int $calendarId
     *
     * @throws SynchronizerException
     *
     * @return array
     */
    public function synchronizeCalendar(int $calendarId): array
    {
        $calendar = $this->getCalendarModel($calendarId);
        $productIds = $this->getProductIds($calendar->regiondo_products);

        // Throw an exception if calendar has no product IDs
        if (0 === \count($productIds)) {
            throw new SynchronizerException(\sprintf('Calendar ID %s has no Regiondo products', $calendarId));
        }

        $client = $this->clientFactory->create();

        // Throw an exception if products could not be fetched
        if (null === ($products = $client->getProducts($productIds))) {
            throw new SynchronizerException(\sprintf('Unable to fetch Regiondo products for calendar ID %s', $calendarId));
        }

        $time = \time();

        $stats = [
            'calendar' => $calendarId,
            'products' => [],
            'created' => 0,
            'deleted' => 0,
            'updated' => 0,
        ];

        // Go through each product and sync it with calendar
        foreach ($products as $product) {
            $productId = (int) $product['product_id'];
            $result = $this->synchronizeProductWithCalendar($client, $productId, $calendarId, $time);

            $stats['products'][] = $productId;
            $stats['created'] += $result['created'];
            $stats['updated'] += $result['updated'];
            $stats['deleted'] += $result['deleted'];
        }

        // Delete the events of products that are no longer present in the calendar settings
        if (\count($stats['products']) > 0) {
            $obsolete = $this->db->fetchAll('SELECT * FROM tl_calendar_events WHERE pid=? AND regiondo_product>0 AND regiondo_product NOT IN ('.\implode(',', $stats['products']).')', [$calendarId]);
        } else {
            $obsolete = $this->db->fetchAll('SELECT * FROM tl_calendar_events WHERE pid=? AND regiondo_product>0', [$calendarId]);
        }

        $stats['deleted'] += $this->deleteEvents($obsolete);

        // Flush the cache
        $this->clientFactory->flushCache();

        // Log the sync result
        $this->logger->info('Calendar has been synchronized with Regiondo events', $stats);

        // Update the last sync
        $calendar->regiondo_lastSync = $time;
        $calendar->save();

        return $stats;
    }

    /**
     * Delete the calendar.
     *
     * @param int $calendarId
     */
    public function deleteCalendar(int $calendarId): void
    {
        $this->getCalendarModel($calendarId);

        $obsolete = $this->db->fetchAll('SELECT * FROM tl_calendar_events WHERE pid=? AND regiondo_product>0', [$calendarId]);

        $this->deleteEvents($obsolete);
        $this->getCalendarFolder($calendarId)->delete();
    }

    /**
     * Get the fields mapper.
     *
     * @return array
     */
    public function getFieldsMapper(): array
    {
        return [
            'title' => 'name',
            'alias' => 'url_key',
            'teaser' => 'short_description',
            'location' => 'location_address',
            'startDate' => null, // special handling
            'endDate' => null, // special handling
            'addTime' => null, // special handling
            'startTime' => null, // special handling
            'endTime' => null, // special handling
            'addImage' => null, // special handling
            'singleSRC' => null, // special handling
            'overwriteMeta' => null, // special handling
            'caption' => null, // special handling
        ];
    }

    /**
     * Validate and get the calendar model.
     *
     * @param int $calendarId
     *
     * @throws SynchronizerException
     *
     * @return CalendarModel
     */
    private function getCalendarModel(int $calendarId): CalendarModel
    {
        if (null === ($calendar = CalendarModel::findByPk($calendarId))) {
            throw new SynchronizerException(\sprintf('Calendar ID %s does not exist', $calendarId));
        }

        // Throw an exception if calendar has no features enabled
        if (!$calendar->regiondo_enable) {
            throw new SynchronizerException(\sprintf('Calendar ID %s has no Regiondo features enabled', $calendarId));
        }

        return $calendar;
    }

    /**
     * Synchronize the product with calendar and return stats.
     *
     * @param Client $client
     * @param int    $productId
     * @param int    $calendarId
     * @param int    $tstamp
     *
     * @return array
     */
    private function synchronizeProductWithCalendar(Client $client, int $productId, int $calendarId, int $tstamp): array
    {
        if (null === ($product = $client->getProduct($productId))) {
            return [];
        }

        $variations = $this->getProductVariations($client, $product);
        $created = 0;
        $updated = 0;

        // Create or update the events
        if (\count($variations) > 0) {
            $basicEvent = $this->generateEventData($client, $product, $calendarId, $tstamp);

            foreach ($variations as $variation) {
                /** @var \DateTime $date */
                $date = $variation['date'];

                $event = $basicEvent;
                $event['regiondo_variationId'] = $variation['id'];
                $event['regiondo_variationName'] = $variation['name'];
                $event['regiondo_timeZone'] = $variation['timeZone'] ?: '';
                $event['regiondo_data'] = \json_encode(\array_merge($product, ['options' => $client->getAvailableOptions($variation['id'], $date)]));
                $event['addTime'] = 1;
                $event['startDate'] = $date->getTimestamp();
                $event['startTime'] = $date->getTimestamp();
                $event['endTime'] = $date->getTimestamp();

                $eventId = $this->db->fetchColumn(
                    'SELECT id FROM tl_calendar_events WHERE pid=? AND regiondo_product=? AND startTime=?',
                    [$calendarId, $productId, $date->getTimestamp()]
                );

                // Create a new record
                if (false === $eventId) {
                    $created += $this->createEvent($event, $productId);
                } else {
                    // Update the existing record
                    $updated += $this->updateEvent($event, $eventId);
                }
            }
        }

        $obsolete = $this->db->fetchAll(
            'SELECT * FROM tl_calendar_events WHERE pid=? AND regiondo_product=? AND regiondo_lastSync<?',
            [$calendarId, $productId, $tstamp]
        );

        // Delete the obsolete events
        $deleted = $this->deleteEvents($obsolete);

        return [
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
        ];
    }

    /**
     * Get the product variations.
     *
     * @param Client $client
     * @param array  $product
     *
     * @return \DateTime[]
     */
    private function getProductVariations(Client $client, array $product): array
    {
        $return = [];

        foreach ($product['variations'] as $variation) {
            $variationId = (int) $variation['variation_id'];

            if (null === ($availableDates = $client->getAvailableDates($variationId))) {
                continue;
            }

            foreach ($availableDates as $date => $times) {
                foreach ($times as $time) {
                    foreach ($time as $v) {
                        $return[] = [
                            'id' => $variationId,
                            'name' => $variation['name'],
                            'date' => new \DateTime($date.' '.$v, ($product['timezone'] ? new \DateTimeZone($product['timezone']) : null)),
                            'timeZone' => $product['timezone'] ?: '',
                        ];
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Create the event.
     *
     * @param array $event
     * @param int   $productId
     *
     * @return int
     */
    private function createEvent(array $event, int $productId): int
    {
        $event['regiondo_product'] = $productId;
        $event['published'] = 1;

        $total = $this->db->insert('tl_calendar_events', $event);
        $lastInsertId = $this->db->lastInsertId();

        // Set the alias
        $this->db->update(
            'tl_calendar_events',
            ['alias' => \sprintf('%s-%s', $event['alias'], $lastInsertId)],
            ['id' => $lastInsertId]
        );

        return $total;
    }

    /**
     * Update the event.
     *
     * @param array $event
     * @param int   $eventId
     *
     * @return int
     */
    private function updateEvent(array $event, int $eventId): int
    {
        return $this->db->update('tl_calendar_events', $event, ['id' => $eventId]);
    }

    /**
     * Delete the obsolete events.
     *
     * @param array $events
     *
     * @return int
     */
    private function deleteEvents(array $events): int
    {
        if (0 === \count($events)) {
            return 0;
        }

        $ids = [];
        $uuids = [];

        // Collect the event IDs and file UUIDs
        foreach ($events as $event) {
            $ids[] = $event['id'];

            if (!\in_array($event['singleSRC'], $uuids, true)) {
                $uuids[] = $event['singleSRC'];
            }
        }

        $statement = $this->db->executeQuery('DELETE FROM tl_calendar_events WHERE id IN ('.\implode(',', $ids).')');

        // Delete the obsolete files AFTER the records have been deleted
        if (\count($uuids = \array_filter($uuids)) > 0) {
            $sqlUuids = \array_map(function ($uuid) {
                return "UNHEX('".\bin2hex($uuid)."')";
            }, $uuids);

            // Get the images that are still used by other events
            $existing = $this->db->fetchAll('SELECT DISTINCT(singleSRC) FROM tl_calendar_events WHERE singleSRC IN ('.\implode(',', $sqlUuids).')');

            // Make sure to remove only the images that are not used by any event
            if (null !== ($imageModels = FilesModel::findMultipleByUuids($diff = \array_diff($uuids, $existing)))) {
                /** @var FilesModel $imageModel */
                foreach ($imageModels as $imageModel) {
                    (new File($imageModel->path))->delete();
                }
            }
        }

        return $statement->rowCount();
    }

    /**
     * Generate the basic event data.
     *
     * @param Client $client
     * @param array  $product
     * @param int    $calendarId
     * @param int    $tstamp
     *
     * @return array
     */
    private function generateEventData(Client $client, array $product, int $calendarId, int $tstamp): array
    {
        $event = [
            'pid' => $calendarId,
            'tstamp' => $tstamp,
            'regiondo_lastSync' => $tstamp,
        ];

        // Map the static fields
        foreach ($this->getFieldsMapper() as $k => $v) {
            if (null !== $v && isset($product[$v])) {
                $event[$k] = $product[$v];
            }
        }

        // Get the image
        if ($product['image']) {
            $imageUrl = $product['image'];
            $imageName = \sprintf('%s.%s', $product['product_id'], \pathinfo($imageUrl, PATHINFO_EXTENSION));

            if (null !== ($imageModel = $this->storeImage($client, $calendarId, $imageUrl, $imageName))) {
                $event['addImage'] = 1;
                $event['singleSRC'] = $imageModel->uuid;

                // Set the custom caption
                if ($product['image_label']) {
                    $event['overwriteMeta'] = 1;
                    $event['caption'] = $product['image_label'];
                }
            }
        }

        return $event;
    }

    /**
     * Store the image and return the files model.
     *
     * @param Client $client
     * @param int    $calendarId
     * @param string $url
     * @param string $name
     *
     * @return FilesModel|null
     */
    private function storeImage(Client $client, int $calendarId, string $url, string $name): ?FilesModel
    {
        $folder = $this->getCalendarFolder($calendarId);

        // Create the file
        $file = new File($folder->path.'/'.$name);

        if (!$file->exists()) {
            // Return null if the file can not be fetched
            if (null === ($stream = $client->getImage($url))) {
                return null;
            }

            $file->write($stream->getContents());
            $file->close();
        }

        return $file->getModel();
    }

    /**
     * Get the product IDs.
     *
     * @param string|array $recordIds
     *
     * @return array
     */
    private function getProductIds($recordIds): array
    {
        $recordIds = StringUtil::deserialize($recordIds, true);

        if (0 === \count($recordIds)) {
            return [];
        }

        $productIds = [];
        $records = $this->db->fetchAll('SELECT product FROM tl_regiondo_product WHERE id IN ('.\implode(',', $recordIds).')');

        foreach ($records as $record) {
            $productIds[] = (int) $record['product'];
        }

        return $productIds;
    }

    /**
     * Get the calendar folder.
     *
     * @param int $calendarId
     *
     * @return Folder
     */
    private function getCalendarFolder(int $calendarId): Folder
    {
        // Make sure the base folder exists and is public
        $baseFolder = new Folder($this->assetsFolder);
        $baseFolder->unprotect();

        // Make sure the target folder exists and is public
        $folder = new Folder($this->assetsFolder.'/'.$calendarId);
        $folder->unprotect();

        return $folder;
    }
}
