<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Haste\Util\Format;
use Patchwork\Utf8;

class ReviewsElement extends ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_regiondo_reviews';

    /**
     * @var array
     */
    protected $reviews = [];

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        // Generate a backend wildcard
        if (TL_MODE === 'BE') {
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE'][$this->type][0]).' ###';
            $template->title = $this->headline;

            return $template->parse();
        }

        if (null === ($this->reviews = \json_decode($this->regiondo_reviews, true))) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the content element.
     */
    protected function compile(): void
    {
        $reviews = $this->reviews;

        // Limit the number of reviews
        if ($this->regiondo_reviewsLimit > 0) {
            $reviews = \array_slice($reviews, 0, $this->regiondo_reviewsLimit);
        }

        $this->Template->reviews = $this->generateReviews($reviews);
    }

    /**
     * Generate the reviews.
     *
     * @param array $items
     *
     * @return array
     */
    protected function generateReviews(array $items)
    {
        $reviews = [];

        foreach ($items as $item) {
            $review = $item;
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $item['created_at'], new \DateTimeZone('UTC'));

            // Format the date
            if (false !== $date) {
                $review['created_timestamp'] = $date->getTimestamp();
                $review['created_datetime'] = $date->format(\DateTime::W3C);
                $review['created_at'] = Format::datim($date->getTimestamp());
            }

            $reviews[] = $item;
        }

        return $reviews;
    }
}
