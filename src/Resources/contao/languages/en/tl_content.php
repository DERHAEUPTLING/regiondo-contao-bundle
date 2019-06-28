<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

use Derhaeuptling\RegiondoBundle\ContentElement\ReviewsElement;

$GLOBALS['TL_LANG']['tl_content']['regiondo_calendar'] = ['Regiondo calendar', 'Please choose the Regiondo calendar.'];
$GLOBALS['TL_LANG']['tl_content']['regiondo_filterProducts'] = ['Filter Regiondo products', 'Enable to select a range of products. If disabled all available products will be used.'];
$GLOBALS['TL_LANG']['tl_content']['regiondo_products'] = ['Regiondo products', 'Please choose one or more Regiondo products to get the reviews from.'];
$GLOBALS['TL_LANG']['tl_content']['regiondo_voucher'] = ['Regiondo voucher', 'Please choose the Regiondo voucher.'];
$GLOBALS['TL_LANG']['tl_content']['regiondo_eventTemplate'] = ['Regiondo event template', 'Here you can choose the Regiondo event template.'];
$GLOBALS['TL_LANG']['tl_content']['regiondo_iframeWidth'] = ['Regiondo iFrame width (px)', 'Here you can enter the Regiondo iFrame width in pixels.'];
$GLOBALS['TL_LANG']['tl_content']['regiondo_reviewsLimit'] = ['Reviews limit', 'Here you can limit the number of displayed reviews. Set 0 to display all reviews.'];
$GLOBALS['TL_LANG']['tl_content']['regiondo_syncReviews'] = ['Synchronize reviews', 'Synchronize the Regiondo reviews upon content element save.'];
$GLOBALS['TL_LANG']['tl_content']['regiondo_reviewsDisplayMode'] = ['Display mode'];
$GLOBALS['TL_LANG']['tl_content']['regiondo_reviewsDisplayMode_options'] = [
    ReviewsElement::SHOW_REVIEWS => 'Show reviews',
    ReviewsElement::SHOW_AGGREGATED_REVIEWS => 'Show aggregated reviews',
    ReviewsElement::SHOW_REVIEWS | ReviewsElement::SHOW_AGGREGATED_REVIEWS => 'Show both reviews and aggregated reviews',
];
$GLOBALS['TL_LANG']['tl_content']['regiondo_ref'] = ['Redirect link', 'Examples: {{link_url::pagealias}} to alias, ID or other insert tag, http://example.com'];
