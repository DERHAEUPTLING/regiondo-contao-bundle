<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\Exception;

use Throwable;

class ClientException extends \RuntimeException
{
    /**
     * @var array
     */
    private $context = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(string $message = '', array $context = [], Throwable $previous = null)
    {
        $this->context = $context;

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
