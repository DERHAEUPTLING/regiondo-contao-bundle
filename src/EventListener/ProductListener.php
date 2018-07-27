<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\EventListener;

use Contao\Controller;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Contao\System;
use Derhaeuptling\RegiondoBundle\ClientFactory;
use Derhaeuptling\RegiondoBundle\Exception\SynchronizerException;
use Derhaeuptling\RegiondoBundle\Synchronizer;
use Doctrine\DBAL\Connection;

class ProductListener
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var Synchronizer
     */
    private $synchronizer;

    /**
     * ProductListener constructor.
     *
     * @param ClientFactory $clientFactory
     * @param Connection    $db
     * @param Synchronizer  $synchronizer
     */
    public function __construct(ClientFactory $clientFactory, Connection $db, Synchronizer $synchronizer)
    {
        $this->clientFactory = $clientFactory;
        $this->db = $db;
        $this->synchronizer = $synchronizer;
    }

    /**
     * On load callback.
     */
    public function onLoadCallback(): void
    {
        // Synchronize the products
        if ('sync' === Input::get('act')) {
            try {
                $stats = $this->synchronizer->synchronizeProducts();
            } catch (SynchronizerException $e) {
                $stats = null;
                Message::addError($GLOBALS['TL_LANG']['tl_regiondo_product']['syncError']);
            }

            if (null !== $stats) {
                Message::addConfirmation(\sprintf($GLOBALS['TL_LANG']['tl_regiondo_product']['syncConfirm'], $stats['created'], $stats['updated'], $stats['obsolete']));
            }

            Controller::redirect(System::getReferer());
        }
    }

    /**
     * On submit callback.
     *
     * @param DataContainer $dc
     */
    public function onSubmitCallback(DataContainer $dc): void
    {
        // Automatically set the product name if none provided
        if (!$dc->activeRecord->name
            && null !== ($product = $this->clientFactory->create()->getProduct((int) $dc->activeRecord->product))
        ) {
            $this->db->update('tl_regiondo_product', ['name' => $product['name']], ['id' => $dc->id]);
        }
    }

    /**
     * On label callback.
     *
     * @param array $row
     *
     * @return string
     */
    public function onLabelCallback(array $row): string
    {
        $buffer = \sprintf('%s <span style="padding-left:3px;color:#b3b3b3;">[%s]</span>', $row['name'], $row['product']);

        if ($row['obsolete']) {
            $buffer .= \sprintf(' <span class="tl_red" style="padding-left:3px;">[%s]</span>', $GLOBALS['TL_LANG']['tl_regiondo_product']['obsolete'][0]);
        }

        return $buffer;
    }

    /**
     * On get the Regiondo products.
     *
     * @return array
     */
    public function onGetRegiondoProducts(): array
    {
        if (null === ($productsData = $this->clientFactory->create()->getProducts())) {
            return [];
        }

        $products = [];

        foreach ($productsData as $product) {
            $products[$product['product_id']] = $product['name'];
        }

        \asort($products);

        return $products;
    }

    /**
     * On get products options callback.
     *
     * @param DataContainer $dc
     *
     * @return array
     */
    public function onGetProductsOptionsCallback(DataContainer $dc): array
    {
        // Synchronize the products if this is not a list view
        if ($dc->id) {
            try {
                $this->synchronizer->synchronizeProducts();
            } catch (SynchronizerException $e) {
            }
        }

        $products = [];
        $records = $this->db->fetchAll('SELECT id, name FROM tl_regiondo_product ORDER BY name');

        foreach ($records as $record) {
            $products[$record['id']] = $record['name'];
        }

        return $products;
    }

    /**
     * On get voucher options callback.
     *
     * @return array
     */
    public function onGetVoucherOptionsCallback(): array
    {
        if (null === ($vouchersData = $this->clientFactory->create()->getVouchers())) {
            return [];
        }

        $vouchers = [];

        foreach ($vouchersData as $voucher) {
            $vouchers[$voucher['product_supplier_id'].'_'.$voucher['product_id']] = $voucher['name'];
        }

        \asort($vouchers);

        return $vouchers;
    }
}
