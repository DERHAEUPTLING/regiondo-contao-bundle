<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\Entity;

class Booking
{
    /**
     * @var Event
     */
    private $event;

    /**
     * @var Reservation[]|array
     */
    private $reservations = [];

    /**
     * Booking constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @return array|Reservation[]
     */
    public function getReservations(): array
    {
        return $this->reservations;
    }

    /**
     * @param array|Reservation[] $reservations
     */
    public function setReservations(array $reservations): void
    {
        /** @var Reservation $reservation */
        foreach ($reservations as $reservation) {
            $this->reservations[$reservation->getOption()] = $reservation;
        }
    }

    /**
     * @param Reservation $reservation
     */
    public function addReservation(Reservation $reservation): void
    {
        $this->reservations[$reservation->getOption()] = $reservation;
    }

    /**
     * @param int $option
     *
     * @return Reservation|null
     */
    public function getReservation(int $option): ?Reservation
    {
        if (!\array_key_exists($option, $this->reservations)) {
            return null;
        }

        return $this->reservations[$option];
    }
}
