<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\Entity;

class Reservation
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var int
     */
    private $option;

    /**
     * @var \DateTime
     */
    private $ttl;

    /**
     * @var int
     */
    private $quantity;

    /**
     * Reservation constructor.
     *
     * @param string    $code
     * @param int       $option
     * @param \DateTime $ttl
     * @param int       $quantity
     */
    public function __construct(string $code, int $option, \DateTime $ttl, int $quantity)
    {
        $this->code = $code;
        $this->option = $option;
        $this->ttl = $ttl;
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return int
     */
    public function getOption(): int
    {
        return $this->option;
    }

    /**
     * @param int $option
     */
    public function setOption(int $option): void
    {
        $this->option = $option;
    }

    /**
     * @return \DateTime
     */
    public function getTtl(): \DateTime
    {
        return $this->ttl;
    }

    /**
     * @param \DateTime $ttl
     */
    public function setTtl(\DateTime $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * Create the instance from Regiondo response.
     *
     * @param array $response
     * @param int   $optionId
     * @param int   $quantity
     *
     * @return Reservation
     */
    public static function createFromResponse(array $response, int $optionId, int $quantity): self
    {
        $date = new \DateTime(
            $response['reservation_data']['reservation_end'],
            new \DateTimeZone($response['reservation_data']['timezone'])
        );

        return new self($response['reservation_data']['reservation_code'], $optionId, $date, $quantity);
    }
}
