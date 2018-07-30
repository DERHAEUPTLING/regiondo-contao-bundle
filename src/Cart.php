<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle;

use Derhaeuptling\RegiondoBundle\Entity\Booking;
use Haste\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class Cart
{
    /**
     * @var BookingManager
     */
    private $bookingManager;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var DataFormatter
     */
    private $dataFormatter;

    /**
     * Cart constructor.
     *
     * @param BookingManager $bookingManager
     * @param ClientFactory  $clientFactory
     * @param DataFormatter  $dataFormatter
     */
    public function __construct(
        BookingManager $bookingManager,
        ClientFactory $clientFactory,
        DataFormatter $dataFormatter
    ) {
        $this->bookingManager = $bookingManager;
        $this->clientFactory = $clientFactory;
        $this->dataFormatter = $dataFormatter;
    }

    /**
     * Create the cart form.
     *
     * @param Request $request
     * @param int     $moduleId
     * @param int     $pageId
     * @param array   $formFields
     *
     * @return Form
     */
    public function createForm(Request $request, int $moduleId, int $pageId, array $formFields): Form
    {
        $form = new Form(\sprintf('regiondo-cart-%s', $moduleId), 'POST', function ($haste) use ($request) {
            return $request->request->get('FORM_SUBMIT') === $haste->getFormId();
        });

        $form->addContaoHiddenFields();
        $form->setFormActionFromPageId($pageId);

        foreach ($formFields as $formField) {
            $form->addFormField($formField['name'], [
                'inputType' => 'select',
                'value' => $formField['quantity'],
                'options' => $formField['options'],
            ]);
        }

        $form->addSubmitFormField('submit', $GLOBALS['TL_LANG']['MSC']['regiondo.cart.submit']);

        return $form;
    }

    /**
     * Generate the option form fields data.
     *
     * @param array $bookings
     * @param array $bookingOptions
     *
     * @return array
     */
    public function generateOptionFormFields(array $bookings, array $bookingOptions): array
    {
        $formFields = [];

        /** @var Booking $booking */
        foreach ($bookings as $booking) {
            $event = $booking->getEvent();

            foreach ($bookingOptions[$event->getEventId()] as $option) {
                $optionId = (int) $option['option_id'];
                $eventId = $event->getEventId();
                $reservation = $booking->getReservation($optionId);
                $quantity = (null !== $reservation) ? $reservation->getQuantity() : 0;

                $formFields[$eventId.'_'.$optionId] = [
                    'name' => \sprintf('reservation_%s_%s', $eventId, $optionId),
                    'event' => $eventId,
                    'option' => $optionId,
                    'quantity' => $quantity,
                    'options' => \range(0, (int) $option['qty_left']),
                ];
            }
        }

        return $formFields;
    }

    /**
     * Get the bookings options grouped by event ID.
     *
     * @param array $bookings
     *
     * @return array
     */
    public function getBookingOptions(array $bookings): array
    {
        $options = [];

        /** @var Booking $booking */
        foreach ($bookings as $booking) {
            $event = $booking->getEvent();
            $options[$event->getEventId()] = $this->getClient()->getAvailableOptions($event->getVariationId(), $event->getDate());
        }

        return $options;
    }

    /**
     * Process the bookings.
     *
     * @param Form  $form
     * @param array $formFields
     * @param array $bookings
     *
     * @return array
     */
    public function processBookings(Form $form, array $formFields, array $bookings): array
    {
        $submittedReservations = [];

        // Collect the submitted reservations
        foreach ($formFields as $formField) {
            $submittedReservations[$formField['event']][$formField['option']] = (int) $form->fetch($formField['name']);
        }

        // Process the submitted reservations
        foreach ($submittedReservations as $eventId => $options) {
            $this->bookingManager->addBooking($eventId, $options, $bookings);
        }

        return $bookings;
    }

    /**
     * Generate the cart items.
     *
     * @param array $bookings
     * @param array $options
     * @param array $optionsFormFields
     *
     * @return array
     */
    public function generateCartItems(array $bookings, array $options, array $optionsFormFields): array
    {
        $total = 0;

        /** @var Booking $booking */
        foreach ($bookings as $booking) {
            $event = $booking->getEvent();

            if (null === ($eventModel = $event->getModel())) {
                continue;
            }

            $eventId = $event->getEventId();

            $items[$eventId] = [
                'title' => $eventModel->title,
                'date' => $this->dataFormatter->formatDate((int) $eventModel->startTime),
                'reservations' => [],
            ];

            // Compute the items and form fields
            foreach ($options[$eventId] as $option) {
                $optionId = (int) $option['option_id'];
                $reservation = $booking->getReservation($optionId);
                $quantity = (null !== $reservation) ? $reservation->getQuantity() : 0;
                $reservationTotal = $option['regiondo_price'] * $quantity;

                $items[$eventId]['reservations'][] = [
                    'name' => $option['name'],
                    'fieldName' => $optionsFormFields[$eventId.'_'.$optionId]['name'],
                    'quantity' => $quantity,
                    'price' => $this->dataFormatter->formatPrice($option['regiondo_price']),
                    'total' => $this->dataFormatter->formatPrice($reservationTotal),
                    'option' => $option,
                ];

                $total += $reservationTotal;
            }
        }

        return $items;
    }

    /**
     * Generate the total price.
     *
     * @param array $bookings
     * @param array $options
     *
     * @return string
     */
    public function generateTotalPrice(array $bookings, array $options): string
    {
        $total = 0;

        /** @var Booking $booking */
        foreach ($bookings as $booking) {
            foreach ($options[$booking->getEvent()->getEventId()] as $option) {
                if (null !== ($reservation = $booking->getReservation((int) $option['option_id']))) {
                    $total += ((float) $option['regiondo_price']) * $reservation->getQuantity();
                }
            }
        }

        return $this->dataFormatter->formatPrice($total);
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
