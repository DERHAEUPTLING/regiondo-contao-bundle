<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle;

use Haste\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class Checkout
{
    /**
     * @var BookingManager
     */
    private $bookingManager;

    /**
     * CartHelper constructor.
     *
     * @param BookingManager $bookingManager
     */
    public function __construct(BookingManager $bookingManager)
    {
        $this->bookingManager = $bookingManager;
    }

    /**
     * Create the checkout form.
     *
     * @param Request $request
     * @param int     $moduleId
     * @param int     $pageId
     * @param array   $formFields
     * @param array   $contactFields
     * @param bool    $isRefresh
     *
     * @return Form
     */
    public function createForm(Request $request, int $moduleId, int $pageId, array $formFields, array $contactFields, bool $isRefresh): Form
    {
        $form = new Form(\sprintf('regiondo-checkout-%s', $moduleId), 'POST', function ($haste) use ($request) {
            return $request->request->get('FORM_SUBMIT') === $haste->getFormId();
        });

        $form->addContaoHiddenFields();
        $form->setFormActionFromPageId($pageId);

        // Add the option form fields
        foreach ($formFields as $formField) {
            $form->addFormField($formField['name'], [
                'inputType' => 'select',
                'value' => $formField['quantity'],
                'options' => $formField['options'],
                'eval' => ['data-regiondo-option' => true],
            ]);
        }

        // Add the contact data fields
        foreach ($contactFields as $name => $field) {
            $labelKey = 'regiondo.checkout.contact.'.$name;

            $form->addFormField($field, [
                'label' => isset($GLOBALS['TL_LANG']['MSC'][$labelKey]) ? $GLOBALS['TL_LANG']['MSC'][$labelKey] : $name,
                'value' => $request->request->has($field) ? $request->request->get($field) : null,
                'inputType' => 'text',
                'eval' => [
                    'mandatory' => !$isRefresh,
                    'rgxp' => ('email' === $name) ? 'email' : null,
                ],
            ]);
        }

        $form->addSubmitFormField('submit', $GLOBALS['TL_LANG']['MSC']['regiondo.checkout.submit']);

        return $form;
    }

    /**
     * Generate the contact form fields.
     *
     * @param array $checkoutData
     *
     * @return array
     */
    public function generateContactFormFields(array $checkoutData): array
    {
        $contactFormFields = [];

        if (isset($checkoutData['contact_data_required'])) {
            foreach ($checkoutData['contact_data_required'] as $field) {
                $contactFormFields[$field] = 'contact_'.$field;
            }
        }

        return $contactFormFields;
    }

    /**
     * Process the purchase.
     *
     * @param Form  $form
     * @param array $contactFormFields
     * @param array $bookings
     *
     * @return array|null
     */
    public function processPurchase(Form $form, array $contactFormFields, array &$bookings): ?array
    {
        $contactData = [];

        foreach ($contactFormFields as $name => $field) {
            $contactData[$name] = $form->fetch($field);
        }

        return $this->bookingManager->purchaseBookings($bookings, $contactFormFields);
    }
}
