<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\Entity;

use Contao\CalendarEventsModel;

class Event
{
    /**
     * @var int
     */
    private $productId;

    /**
     * @var int
     */
    private $variationId;

    /**
     * @var \Datetime
     */
    private $date;

    /**
     * @var CalendarEventsModel
     */
    private $model;

    /**
     * EVent constructor.
     *
     * @param int       $productId
     * @param int       $variationId
     * @param \Datetime $date
     */
    public function __construct(int $productId, int $variationId, \Datetime $date)
    {
        $this->productId = $productId;
        $this->variationId = $variationId;
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @return int
     */
    public function getVariationId(): int
    {
        return $this->variationId;
    }

    /**
     * @return \Datetime
     */
    public function getDate(): \Datetime
    {
        return $this->date;
    }

    /**
     * @return int|null
     */
    public function getEventId(): ?int
    {
        if (null === $this->model) {
            return null;
        }

        return (int) $this->model->id;
    }

    /**
     * @return CalendarEventsModel|null
     */
    public function getModel(): ?CalendarEventsModel
    {
        return $this->model;
    }

    /**
     * Create from event model.
     *
     * @param CalendarEventsModel $model
     *
     * @throws \InvalidArgumentException
     *
     * @return Event
     */
    public static function createFromModel(CalendarEventsModel $model): self
    {
        if (!$model->regiondo_product || !$model->regiondo_variationId) {
            throw new \InvalidArgumentException(\sprintf('The event ID %s is not eligible for Regiondo', $model->id));
        }

        $date = new \DateTime();
        $date->setTimestamp((int) $model->startTime);

        if ($model->regiondo_timeZone) {
            $date->setTimezone(new \DateTimeZone($model->regiondo_timeZone));
        }

        $event = new self((int) $model->regiondo_product, (int) $model->regiondo_variationId, $date);
        $event->model = $model;

        return $event;
    }
}
