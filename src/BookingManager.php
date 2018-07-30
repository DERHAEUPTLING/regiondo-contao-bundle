<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Derhaeuptling\RegiondoBundle\Entity\Booking;
use Derhaeuptling\RegiondoBundle\Entity\Event;
use Derhaeuptling\RegiondoBundle\Entity\Reservation;
use Symfony\Component\HttpFoundation\Request;

class BookingManager
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * BookingManager constructor.
     *
     * @param ClientFactory            $clientFactory
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ClientFactory $clientFactory, ContaoFrameworkInterface $framework)
    {
        $this->clientFactory = $clientFactory;
        $this->framework = $framework;
    }

    /**
     * Add or update the booking.
     *
     * @param int   $eventId
     * @param array $options
     * @param array &$bookings
     *
     * @return bool
     */
    public function addBooking(int $eventId, array $options, array &$bookings): bool
    {
        $newBooking = new Booking($this->createEvent($eventId));

        // First remove the existing reservations for this event, if any
        /** @var Booking $booking */
        foreach ($bookings as $index => $booking) {
            if ($booking->getEvent()->getEventId() === $newBooking->getEvent()->getEventId()) {
                /** @var Reservation $reservation */
                foreach ($booking->getReservations() as $reservation) {
                    $this->getClient()->removeProductReservation($reservation->getCode());
                }

                unset($bookings[$index]);
                break;
            }
        }

        $reservations = [];

        // Create the reservations
        foreach ($options as $optionId => $optionQuantity) {
            if (0 === $optionQuantity) {
                continue;
            }

            $response = $this->getClient()->createProductReservation(
                $newBooking->getEvent()->getProductId(),
                $newBooking->getEvent()->getDate(),
                $optionId,
                $optionQuantity
            );

            if (null !== $response) {
                $reservations[] = Reservation::createFromResponse($response, $optionId, $optionQuantity);
            }
        }

        // Return null if no new reservations were added
        if (0 === \count($reservations)) {
            return false;
        }

        $newBooking->setReservations($reservations);
        $bookings[$eventId] = $newBooking;

        return true;
    }

    /**
     * Get the checkout data.
     *
     * @param array $bookings
     *
     * @return array|null
     */
    public function getCheckoutData(array $bookings): ?array
    {
        return $this->getClient()->getCheckoutTotals($this->convertBookingsToItems($bookings));
    }

    /**
     * Purchase the bookings.
     *
     * @param array &$bookings
     * @param array $contactData
     *
     * @return array|null
     */
    public function purchaseBookings(array &$bookings, array $contactData): ?array
    {
        $response = $this->getClient()->purchaseItems($this->convertBookingsToItems($bookings), $contactData);

        if (null !== $response) {
            $bookings = [];
        }

        return $response;
    }

    /**
     * Get the bookings from request grouped by event ID.
     *
     * @param Request $request
     *
     * @return array
     */
    public function getBookingsFromRequest(Request $request): array
    {
        if (!$request->headers->has('X-Regiondo-Bookings')) {
            return [];
        }

        $currentReservations = \json_decode($request->headers->get('X-Regiondo-Bookings'), true);

        if (null === $currentReservations) {
            return [];
        }

        $bookings = [];

        // Validate the current reservations
        foreach ($currentReservations as $reservationData) {
            $eventId = (int) $reservationData['event'];

            // Create the booking instance
            if (!isset($bookings[$eventId])) {
                $bookings[$eventId] = new Booking($this->createEvent($eventId));
            }

            if (null !== ($response = $this->getClient()->prolongProductReservation($reservationData['code']))) {
                // @todo – if quantity is in the response one day, use that
                $bookings[$eventId]->addReservation(
                    Reservation::createFromResponse($response, (int) $reservationData['option'], (int) $reservationData['quantity'])
                );
            }
        }

        return $bookings;
    }

    /**
     * Get the booking reservations for response.
     *
     * @param array $bookings
     *
     * @return array
     */
    public function getBookingsForResponse(array $bookings): array
    {
        $reservations = [];

        /** @var Booking $booking */
        foreach ($bookings as $booking) {
            /** @var Reservation $reservation */
            foreach ($booking->getReservations() as $reservation) {
                $reservations[] = [
                    'event' => $booking->getEvent()->getEventId(),
                    'option' => $reservation->getOption(),
                    'code' => $reservation->getCode(),
                    'ttl' => $reservation->getTtl()->getTimestamp(),
                    'quantity' => $reservation->getQuantity(),
                ];
            }
        }

        return $reservations;
    }

    /**
     * Create the event entity.
     *
     * @param int $eventId
     *
     * @throws \Exception
     *
     * @return Event
     */
    public function createEvent(int $eventId): Event
    {
        if (!$eventId) {
            throw new \Exception('The event ID was not provided');
        }

        $this->framework->initialize();

        /** @var CalendarEventsModel $adapter */
        $adapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (null === ($event = $adapter->findByPk($eventId)) || null === ($calendar = $event->getRelated('pid'))) {
            throw new \Exception(\sprintf('The event ID %s was not found', $eventId));
        }

        if (!$event->regiondo_product || !$event->regiondo_variationId || !$calendar->regiondo_enable) {
            throw new \Exception(\sprintf('The event ID %s is not eligible for Regiondo', $eventId));
        }

        return Event::createFromModel($event);
    }

    /**
     * Convert the bookings to items.
     *
     * @param array $bookings
     *
     * @return array
     */
    private function convertBookingsToItems(array $bookings): array
    {
        $items = [];

        /** @var Booking $booking */
        foreach ($bookings as $booking) {
            /** @var Reservation $reservation */
            foreach ($booking->getReservations() as $reservation) {
                $items[] = [
                    'product_id' => $booking->getEvent()->getProductId(),
                    'option_id' => $reservation->getOption(),
                    'date_time' => $booking->getEvent()->getDate()->format('Y-m-d H:i'),
                    'qty' => $reservation->getQuantity(),
                    'reservation_code' => $reservation->getCode(),
                ];
            }
        }

        return $items;
    }

    /**
     * Get the client.
     *
     * @return Client
     */
    private function getClient(): Client
    {
        static $client;

        if (null === $client) {
            $client = $this->clientFactory->create();
        }

        return $client;
    }
}
