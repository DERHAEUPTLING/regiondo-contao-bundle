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
use Contao\PageModel;
use Derhaeuptling\RegiondoBundle\Cart;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(defaults={"_scope" = "frontend", "_token_check" = true})
 */
class CartController extends AjaxController
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * CartController constructor.
     *
     * @param Cart $cart
     */
    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * @Route(
     *     "/_regiondo_cart/{page}/{module}",
     *     name="_regiondo_cart",
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
        $template = new FrontendTemplate($module->regiondo_cartTemplate ?: 'regiondo_cart_default');

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

        if (0 === \count($bookings)) {
            return;
        }

        $options = $this->cart->getBookingOptions($bookings);
        $optionsFormFields = $this->cart->generateOptionFormFields($bookings, $options);
        $form = $this->cart->createForm($request, (int) $module->id, (int) $GLOBALS['objPage']->id, $optionsFormFields);
        $formValidated = false;

        // Process the form
        if ($form->validate()) {
            $formValidated = true;
            $bookings = $this->cart->processBookings($form, $optionsFormFields, $bookings);
            $template->success = true;
        }

        // Generate the bookings now as they might have been altered by the form process
        if (0 === \count($bookings)) {
            return;
        }

        // Re-initialize the form as the Regiondo options might have changed
        if ($formValidated) {
            $options = $this->cart->getBookingOptions($bookings);
            $optionsFormFields = $this->cart->generateOptionFormFields($bookings, $options);
            $form = $this->cart->createForm($request, (int) $module->id, (int) $GLOBALS['objPage']->id, $optionsFormFields);
        }

        $items = $this->cart->generateCartItems($bookings, $options, $optionsFormFields);

        $template->items = $items;
        $template->total = $this->cart->generateTotalPrice($bookings, $options);
        $template->form = $form->getHelperObject();

        // Generate the checkout URL
        if (\count($items) > 0 && null !== ($checkoutPage = PageModel::findPublishedById($module->jumpTo))) {
            $template->checkoutUrl = $checkoutPage->getFrontendUrl();
        }
    }
}
