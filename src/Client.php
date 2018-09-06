<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle;

use Derhaeuptling\RegiondoBundle\Exception\ClientException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class Client
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Client constructor.
     *
     * @param GuzzleClient    $client
     * @param LoggerInterface $logger
     */
    public function __construct(GuzzleClient $client, LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Get the products.
     *
     * @param array $ids
     *
     * @return array|null
     */
    public function getProducts(array $ids = null): ?array
    {
        try {
            $response = $this->request('GET', 'products');
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return null;
            }
        }

        if (null === $response || !isset($response['data'])) {
            return null;
        }

        $data = $response['data'];

        // Filter by IDs
        if (null !== $ids) {
            $ids = \array_map('intval', $ids);
            $data = \array_filter($data, function ($item) use ($ids) {
                return \in_array((int) $item['product_id'], $ids, true);
            });
        }

        return $data;
    }

    /**
     * Get the product.
     *
     * @param int $id
     *
     * @return array|null
     */
    public function getProduct(int $id): ?array
    {
        if (!$id) {
            return null;
        }

        try {
            $response = $this->request('GET', 'products/'.$id);
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return null;
            }
        }

        if (null === $response || !isset($response['data'])) {
            return null;
        }

        return $response['data'];
    }

    /**
     * Get available dates for the product variation ID.
     *
     * @param int       $variationId
     * @param \DateTime $from
     * @param \DateTime $to
     *
     * @return array|null
     */
    public function getAvailableDates(int $variationId, \DateTime $from = null, \DateTime $to = null): ?array
    {
        $from = (null !== $from) ? $from : new \DateTime();
        $to = (null !== $to) ? $to : new \DateTime('+2 years');

        try {
            $response = $this->request(
                'GET',
                'products/availabilities/'.$variationId,
                [
                    'query' => [
                        'dt_from' => $from->format('Y-m-d'),
                        'dt_to' => $to->format('Y-m-d'),
                    ],
                ]
            );
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return null;
            }
        }

        if (null === $response || !isset($response['data'])) {
            return null;
        }

        return $response['data'];
    }

    /**
     * Get available options.
     *
     * @param int       $variationId
     * @param \DateTime $date
     *
     * @return array
     */
    public function getAvailableOptions(int $variationId, \DateTime $date): array
    {
        if (!$variationId) {
            return [];
        }

        try {
            $response = $this->request(
                'GET',
                'products/availoptions/'.$variationId,
                [
                    'query' => [
                        'date' => $date->format('Y-m-d'),
                        'time' => $date->format('H:i'),
                    ],
                ]
            );
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return [];
            }
        }

        if (null === $response || !isset($response['data'])) {
            return [];
        }

        return $response['data'];
    }

    /**
     * Create the product reservation.
     *
     * @param int       $productId
     * @param \DateTime $date
     * @param int       $optionId
     * @param int       $quantity
     *
     * @return array|null
     */
    public function createProductReservation(int $productId, \DateTime $date, int $optionId, int $quantity): ?array
    {
        if (!$productId || !$optionId || !$quantity) {
            return null;
        }

        try {
            $response = $this->request(
                'POST',
                'checkout/hold',
                [
                    'json' => [
                        'product_id' => $productId,
                        'date_time' => $date->format('Y-m-d H:i'),
                        'option_id' => $optionId,
                        'qty' => $quantity,
                    ],
                ],
                [202]
            );
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return null;
            }
        }

        return $response;
    }

    /**
     * Prolong the product reservation.
     *
     * @param string $code
     *
     * @return array|null
     */
    public function prolongProductReservation(string $code): ?array
    {
        if (!$code) {
            return null;
        }

        try {
            $response = $this->request('PUT', 'checkout/hold', ['query' => ['reservation_code' => $code]], [202]);
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return null;
            }
        }

        return $response;
    }

    /**
     * Remove the product reservation.
     *
     * @param string $code
     *
     * @return array|null
     */
    public function removeProductReservation(string $code): ?array
    {
        if (!$code) {
            return null;
        }

        try {
            $response = $this->request('DELETE', 'checkout/hold', ['query' => ['reservation_code' => $code]], [202]);
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return null;
            }
        }

        return $response;
    }

    /**
     * Get the checkout totals.
     *
     * @param array $items
     *
     * @return array
     */
    public function getCheckoutTotals(array $items): array
    {
        try {
            $response = $this->request('POST', 'checkout/totals', ['json' => ['items' => $items]], [202]);
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return [];
            }
        }

        return (null !== $response) ? $response : [];
    }

    /**
     * Purchase the items.
     *
     * @param array $items
     * @param array $contactData
     *
     * @return array
     */
    public function purchaseItems(array $items, array $contactData): array
    {
        try {
            $response = $this->request(
                'POST',
                'checkout/purchase',
                [
                    'json' => [
                        'items' => $items,
                        'contact_data' => $contactData,
                    ],
                ],
                [202]
            );
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return [];
            }
        }

        return (null !== $response) ? $response : [];
    }

    /**
     * Get the reviews.
     *
     * @param int $productId
     *
     * @return array|null
     */
    public function getReviews(int $productId): ?array
    {
        try {
            $response = $this->request('GET', 'reviews', ['query' => ['product_id' => $productId]]);
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return null;
            }
        }

        if (null === $response || !isset($response['data'])) {
            return null;
        }

        return $response['data'];
    }

    /**
     * Get the vouchers.
     *
     * @return array|null
     */
    public function getVouchers(): ?array
    {
        try {
            $response = $this->request('GET', 'products', ['query' => [
                'is_appointment_needed' => 0,
                'include_value_vouchers' => 1,
            ]]);
        } catch (ClientException $e) {
            if ($this->handleException($e)) {
                return null;
            }
        }

        if (null === $response || !isset($response['data'])) {
            return null;
        }

        return $response['data'];
    }

    /**
     * Get the image.
     *
     * @param string $image
     *
     * @return StreamInterface|null
     */
    public function getImage(string $image): ?StreamInterface
    {
        try {
            try {
                $response = $this->client->request('GET', $image);
            } catch (\Exception $e) {
                throw new ClientException($e->getMessage(), ['method' => 'GET', 'uri' => $image], $e);
            }
        } catch (ClientException $e) {
            // Log the exception if logger is available and return null
            if (null !== $this->logger) {
                $this->logger->notice($e->getMessage(), $e->getContext());

                return null;
            }

            throw $e;
        }

        return $response->getBody();
    }

    /**
     * Request the URI.
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     * @param array  $invalidStatusCodes
     *
     * @throws \Exception
     *
     * @return array|null
     */
    private function request(string $method, string $uri, array $options = [], array $invalidStatusCodes = []): ?array
    {
        $invalidStatusCodes = \array_merge([404], $invalidStatusCodes);

        // Set the default limit if not provided
        $options['query']['limit'] = isset($options['query']['limit']) ? $options['query']['limit'] : 100;

        try {
            $response = $this->client->request($method, $uri, $options);

            if (\in_array($response->getStatusCode(), $invalidStatusCodes, true)) {
                return null;
            }

            $data = $this->parseResponse($response);

            // Pagination
            if (isset($data['page']) && $data['page']['current'] < $data['page']['next']) {
                $options['query']['offset'] = $data['page']['current'] * $data['page']['limit'];
                $nextPageData = $this->request($method, $uri, $options, $invalidStatusCodes);

                if (null !== $nextPageData && \is_array($nextPageData['data'])) {
                    $data['data'] = \array_merge($data['data'], $nextPageData['data']);
                }
            }
        } catch (\Exception $e) {
            throw new ClientException($e->getMessage(), ['method' => $method, 'uri' => $uri, 'options' => $options], $e);
        }

        return $data;
    }

    /**
     * Parse the response.
     *
     * @param Response $response
     *
     * @return array
     */
    private function parseResponse(Response $response): array
    {
        $data = \json_decode($response->getBody()->getContents(), true);

        if (JSON_ERROR_NONE !== \json_last_error() || null === $data) {
            throw new ClientException('Response data could not be decoded', [
                'response' => $data,
                'message' => \json_last_error_msg(),
                'error' => \json_last_error(),
            ]);
        }

        return $data;
    }

    /**
     * Log the exception if logger is available and return true.
     *
     * @param ClientException $e
     *
     * @throws ClientException
     *
     * @return bool
     */
    private function handleException(ClientException $e)
    {
        if (null !== $this->logger) {
            $this->logger->notice($e->getMessage(), $e->getContext());

            return true;
        }

        throw $e;
    }
}
