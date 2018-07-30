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
use Contao\ModuleModel;
use Derhaeuptling\RegiondoBundle\Cart;
use Derhaeuptling\RegiondoBundle\Checkout;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(defaults={"_scope" = "frontend", "_token_check" = true})
 */
class CheckoutController extends AjaxController
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var Checkout
     */
    private $checkout;

    /**
     * CheckoutController constructor.
     *
     * @param Cart     $cart
     * @param Checkout $checkout
     */
    public function __construct(Cart $cart, Checkout $checkout)
    {
        $this->cart = $cart;
        $this->checkout = $checkout;
    }

    /**
     * @Route(
     *     "/_regiondo_checkout/{page}/{module}",
     *     name="_regiondo_checkout",
     *     methods={"GET", "POST"},
     *     condition="request.isXmlHttpRequest()",
     *     requirements={"page":"\d+", "module":"\d+"}
     * )
     */
    public function ajaxAction(Request $request): Response
    {
        try {
            $this->initAjaxEnvironment($request);
            $module = $this->getFrontendModule($request->attributes->getInt('module'));
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $bookings = $this->bookingManager->getBookingsFromRequest($request);
        $template = new FrontendTemplate($module->regiondo_checkoutTemplate ?: 'regiondo_checkout_default');

        $this->compileTemplate($template, $module, $request, $bookings);

        return new JsonResponse([
            'buffer' => $template->parse(),
            'bookings' => $this->bookingManager->getBookingsForResponse($bookings),
        ]);
    }

    /**
     * Compile the template.
     *
     * @param FrontendTemplate $template
     * @param ModuleModel      $module
     * @param Request          $request
     * @param array            &$bookings
     */
    private function compileTemplate(FrontendTemplate $template, ModuleModel $module, Request $request, array &$bookings): void
    {
        $template->setData($module->row());

        if (0 === \count($bookings) || null === ($checkoutData = $this->bookingManager->getCheckoutData($bookings))) {
            return;
        }

        $isRefresh = $request->query->getBoolean('refresh');
        $options = $this->cart->getBookingOptions($bookings);
        $optionsFormFields = $this->cart->generateOptionFormFields($bookings, $options);
        $contactFormFields = $this->checkout->generateContactFormFields($checkoutData);

        $form = $this->checkout->createForm($request, (int) $module->id, (int) $GLOBALS['objPage']->id, $optionsFormFields, $contactFormFields, $isRefresh);
        $formValidated = false;

        // Process the form
        if ($form->validate()) {
            $formValidated = true;
            $bookings = $this->cart->processBookings($form, $optionsFormFields, $bookings);

            // Process the purchase
            if (\count($bookings) > 0 && !$isRefresh) {
                $order = $this->checkout->processPurchase($form, $contactFormFields, $bookings);

                // Add the order to template
                if (null !== $order) {
                    $template->success = true;
                    $template->order = $order;
                } else {
                    $template->error = true;
                }

                return;
            }
        }

        // Return if there are no bookings anymore
        if (0 === \count($bookings)) {
            return;
        }

        // Re-initialize the form as the Regiondo options might have changed
        if ($formValidated) {
            $options = $this->cart->getBookingOptions($bookings);
            $optionsFormFields = $this->cart->generateOptionFormFields($bookings, $options);
            $form = $this->checkout->createForm($request, (int) $module->id, (int) $GLOBALS['objPage']->id, $optionsFormFields, $contactFormFields, $isRefresh);
        }

        $items = $this->cart->generateCartItems($bookings, $options, $optionsFormFields);

        $template->items = $items;
        $template->total = $this->cart->generateTotalPrice($bookings, $options);
        $template->form = $form->getHelperObject();
        $template->contactFields = $contactFormFields;
    }
}
