<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\Controller;

use Contao\FrontendTemplate;
use Derhaeuptling\RegiondoBundle\Entity\Booking;
use Derhaeuptling\RegiondoBundle\Entity\Event;
use Derhaeuptling\RegiondoBundle\EventHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(defaults={"_scope" = "frontend", "_token_check" = true})
 */
class EventBookingController extends AjaxController
{
    /**
     * @var EventHelper
     */
    private $eventHelper;

    /**
     * EventBookingController constructor.
     *
     * @param EventHelper $eventHelper
     */
    public function __construct(EventHelper $eventHelper)
    {
        $this->eventHelper = $eventHelper;
    }

    /**
     * @Route(
     *     "/_regiondo_event_booking/{page}/{content}/{event}",
     *     name="_regiondo_event_booking",
     *     methods={"GET", "POST"},
     *     condition="request.isXmlHttpRequest()",
     *     requirements={"page":"\d+", "content":"\d+", "event":"\d+"}
     * )
     */
    public function ajaxAction(Request $request): Response
    {
        try {
            $this->initAjaxEnvironment($request);
            $event = $this->bookingManager->createEvent($request->attributes->getInt('event'));
            $content = $this->getContentElement($request->attributes->getInt('content'));
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $bookings = $this->bookingManager->getBookingsFromRequest($request);

        $template = new FrontendTemplate($content->regiondo_eventTemplate ?: 'regiondo_event_default');
        $template->setData($content->row());

        $this->compileTemplate($template, $request, $event, $bookings);

        return new JsonResponse([
            'buffer' => $template->parse(),
            'bookings' => $this->bookingManager->getBookingsForResponse($bookings),
        ]);
    }

    /**
     * Compile the template.
     *
     * @param FrontendTemplate $template
     * @param Request          $request
     * @param Event            $event
     * @param array            &$bookings
     */
    private function compileTemplate(FrontendTemplate $template, Request $request, Event $event, array &$bookings): void
    {
        // Form was submitted or refreshed
        if ($request->request->has('event')) {
            $form = $this->eventHelper->createForm($event, $request, $request->request->getInt('event'));

            // Process the form
            if ($form->validate()) {
                $eventId = (int) $form->fetch('event');
                $currentBooking = isset($bookings[$eventId]) ? $bookings[$eventId] : null;
                $options = $this->eventHelper->addOptionsToForm($form, $event, $currentBooking);

                if (0 === \count($options)) {
                    $template->noOptionsAvailable = true;
                } else {
                    $template->options = $options;
                }

                $submittedOptions = $this->eventHelper->processForm($form, $template, $request, $options);

                if (null !== $submittedOptions) {
                    // Add the success or error message
                    if ($this->bookingManager->addBooking($eventId, $submittedOptions, $bookings)) {
                        $template->success = true;
                    } else {
                        $template->error = true;
                    }
                }
            }

            $filteredBookings = $this->eventHelper->filterBookingsByMainEvent($event, $bookings);
        } else {
            $filteredBookings = $this->eventHelper->filterBookingsByMainEvent($event, $bookings);

            // If there is only one booking we can display the prefilled form immediately
            if (1 === \count($filteredBookings)) {
                /** @var Booking $booking */
                $booking = \reset($filteredBookings);
                $form = $this->eventHelper->createForm($event, $request, $booking->getEvent()->getEventId());
                $options = $this->eventHelper->addOptionsToForm($form, $event, $booking);

                if (0 === \count($options)) {
                    $template->noOptionsAvailable = true;
                } else {
                    $template->options = $options;
                }
            } else {
                $form = $this->eventHelper->createForm($event, $request);
            }
        }

        $template->bookings = $this->eventHelper->generateBookingsForTemplate($filteredBookings);
        $template->form = $form->getHelperObject();
    }
}
