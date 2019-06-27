<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @author     Moritz V. <https://github.com/m-vo>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Haste\Util\Format;
use Patchwork\Utf8;

class ReviewsElement extends ContentElement
{
    public const SHOW_REVIEWS = 1 << 0;
    public const SHOW_AGGREGATED_REVIEWS = 1 << 1;

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
        $displayMode = $this->regiondo_reviewsDisplayMode;
        $showReviews = $displayMode & self::SHOW_REVIEWS;
        $showAggregatedReviews = $displayMode & self::SHOW_AGGREGATED_REVIEWS;

        if ($showAggregatedReviews) {
            $this->Template->aggregatedReviews = $this->aggregateReviews($this->reviews);
        }
        $this->Template->showAggregatedReviews = $showAggregatedReviews;

        if ($showReviews) {
            // Limit the number of reviews
            $reviews = $this->reviews;
            if ($this->regiondo_reviewsLimit > 0) {
                $reviews = \array_slice($reviews, 0, $this->regiondo_reviewsLimit);
            }

            $this->Template->reviews = $this->generateReviews($reviews);
        }
        $this->Template->showReviews = $showReviews;
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

    /**
     * Aggregate reviews.
     *
     * @param array $items
     *
     * @return array
     */
    protected function aggregateReviews(array $items): array
    {
        $ratings = \array_filter(
            \array_map(function ($item) {
                if (!$item['vote_details']) {
                    return null;
                }

                // Average vote details
                return $this->calculateAverage(
                    \array_map(static function ($item) {
                        return $item['percent'];
                    }, $item['vote_details'])
                );
            }, $items)
        );

        // Average votes
        return [
            'count' => \count($ratings),
            'percent' => $this->calculateAverage($ratings),
        ];
    }

    /**
     * Calculate average - used to aggregate review details and reviews.
     *
     * @param array $values
     *
     * @return float
     */
    protected function calculateAverage(array $values): float
    {
        $sum = \array_reduce($values,
            static function ($sum, $item) {
                $sum += $item;

                return $sum;
            }
        );

        // Simple arithmetic mean
        return $sum / \count($values);
    }
}
