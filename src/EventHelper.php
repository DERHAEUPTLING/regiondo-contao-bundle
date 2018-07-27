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
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\Date;
use Contao\FrontendTemplate;
use Contao\Model\Collection;
use Derhaeuptling\RegiondoBundle\Entity\Booking;
use Derhaeuptling\RegiondoBundle\Entity\Event;
use Derhaeuptling\RegiondoBundle\Entity\Reservation;
use Haste\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class EventHelper implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var DataFormatter
     */
    private $dataFormatter;

    /**
     * EventHelper constructor.
     *
     * @param ClientFactory $clientFactory
     * @param DataFormatter $dataFormatter
     */
    public function __construct(ClientFactory $clientFactory, DataFormatter $dataFormatter)
    {
        $this->clientFactory = $clientFactory;
        $this->dataFormatter = $dataFormatter;
    }

    /**
     * Filter the bookings by main event.
     *
     * @param Event $event
     * @param array $bookings
     *
     * @return array
     */
    public function filterBookingsByMainEvent(Event $event, array $bookings): array
    {
        $siblings = $this->getEvents((int) $event->getModel()->pid, $event->getProductId(), $event->getVariationId());

        if (null !== $siblings) {
            $ids = \array_map('intval', $siblings->fetchEach('id'));

            /** @var Booking $booking */
            foreach ($bookings as $index => $booking) {
                if (!\in_array($booking->getEvent()->getEventId(), $ids, true)) {
                    unset($bookings[$index]);
                }
            }
        }

        return $bookings;
    }

    /**
     * Generate bookings for template.
     *
     * @param array $bookings
     *
     * @return array
     */
    public function generateBookingsForTemplate(array $bookings): array
    {
        $return = [];

        /** @var Booking $booking */
        foreach ($bookings as $booking) {
            $return[] = $this->dataFormatter->formatDate((int) $booking->getEvent()->getModel()->startTime);
        }

        return $return;
    }

    /**
     * Create the booking form.
     *
     * @param Event    $event
     * @param Request  $request
     * @param int|null $eventValue
     *
     * @return Form
     */
    public function createForm(Event $event, Request $request, int $eventValue = null): Form
    {
        $eventOptions = [];

        // Generate the event options
        if (null !== ($events = $this->getEvents($event->getModel()->pid, $event->getProductId(), $event->getVariationId()))) {
            /** @var CalendarEventsModel $model */
            foreach ($events as $model) {
                $eventOptions[$model->id] = $this->dataFormatter->formatDate((int) $model->startTime);
            }
        }

        $form = new Form(\sprintf('regiondo-event-%s-%s', $event->getProductId(), $event->getVariationId()), 'POST', function ($haste) use ($request) {
            return $request->request->get('FORM_SUBMIT') === $haste->getFormId();
        });

        $form->addContaoHiddenFields();
        $form->setFormActionFromPageId($GLOBALS['objPage']->id);

        $form->addFormField('event', [
            'label' => $GLOBALS['TL_LANG']['MSC']['regiondo.event.date'],
            'value' => $eventValue,
            'inputType' => 'select',
            'options' => $eventOptions,
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'data-regiondo-event' => true],
        ]);

        $form->addSubmitFormField('submit', $GLOBALS['TL_LANG']['MSC']['regiondo.event.submit']);

        return $form;
    }

    /**
     * Process the booking form.
     *
     * @param Form             $form
     * @param FrontendTemplate $template
     * @param Request          $request
     * @param array            $availableOptions
     *
     * @return array|null
     */
    public function processForm(Form $form, FrontendTemplate $template, Request $request, array $availableOptions): ?array
    {
        // Return if there are no options
        if (0 === \count($availableOptions)) {
            $template->noOptionsAvailable = true;

            return null;
        }

        $template->options = $availableOptions;

        // Return the form if the form was refreshed and not submitted (e.g. by select value change),
        // or if the form does not re-validate
        if (($form->isSubmitted() && $request->query->get('refresh')) || !$form->validate()) {
            return null;
        }

        $submittedOptions = [];

        // Get the submitted options
        foreach ($availableOptions as $field) {
            if ($value = $form->fetch($field['name'])) {
                $submittedOptions[$field['id']] = (int) $value;
            }
        }

        // Return if no options were submitted
        if (0 === \count($submittedOptions)) {
            $template->noOptionsSelected = true;

            return null;
        }

        return $submittedOptions;
    }

    /**
     * Add options to the form.
     *
     * @param Form         $form
     * @param Event        $event
     * @param Booking|null $booking
     *
     * @return array
     */
    public function addOptionsToForm(Form $form, Event $event, Booking $booking = null): array
    {
        $fields = [];
        $values = [];
        $options = $this->clientFactory->create()->getAvailableOptions($event->getVariationId(), $event->getDate());

        // Get the values from booking
        if (null !== $booking) {
            /** @var Reservation $reservation */
            foreach ($booking->getReservations() as $reservation) {
                $values[$reservation->getOption()] = $reservation->getQuantity();
            }
        }

        foreach ($options as $option) {
            $id = (int) $option['option_id'];
            $name = 'option_'.$id;

            $form->addFormField($name, [
                'label' => $this->dataFormatter->formatOptionLabel($option),
                'value' => isset($values[$id]) ? $values[$id] : null,
                'inputType' => 'select',
                'options' => \range(0, (int) $option['qty_left']),
            ]);

            $fields[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        return $fields;
    }

    /**
     * Get the events grouped by Regiondo product ID and variation ID.
     *
     * @param int $calendarId
     *
     * @return Collection|null
     */
    public function getEvents(int $calendarId, int $productId = null, int $variationId = null): ?Collection
    {
        $this->framework->initialize();

        /** @var CalendarEventsModel $modelAdapter */
        $modelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        $t = $modelAdapter->getTable();
        $columns = ["$t.pid=?", "$t.regiondo_product>0", "$t.regiondo_variationId>0"];
        $values = [$calendarId];

        // Product ID
        if (null !== $productId) {
            $columns[] = "$t.regiondo_product=?";
            $values[] = $productId;
        }

        // Variation ID
        if (null !== $variationId) {
            $columns[] = "$t.regiondo_variationId=?";
            $values[] = $variationId;
        }

        // Preview mode
        if (!\defined('BE_USER_LOGGED_IN') || !BE_USER_LOGGED_IN) {
            /** @var Date $dateAdapter */
            $dateAdapter = $this->framework->getAdapter(Date::class);

            $time = $dateAdapter->floorToMinute();
            $columns[] = "($t.start='' OR $t.start<=?) AND ($t.stop='' OR $t.stop>?) AND $t.published=1";
            $values[] = $time;
            $values[] = ($time + 60);
        }

        return $modelAdapter->findBy($columns, $values, ['order' => "$t.startTime DESC"]);
    }
}
