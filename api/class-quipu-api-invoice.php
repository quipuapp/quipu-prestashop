<?php
/**
* 2007-2015 PrestaShop.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/*if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly*/

if (!class_exists('QuipuApi')) {
    include_once 'class-quipu-api.php';
}
/*
if ( ! class_exists( 'QuipuApiNumeration' ) ) {
    include_once( 'quipu-api/class-quipu-api-numeration.php' );
}

if ( ! class_exists( 'QuipuApiContact' ) ) {
    include_once( 'quipu-api/class-quipu-api-contact.php' );
}
*/

class QuipuApiInvoice extends QuipuApi
{
    /**
     * @var QuipuApiContact
     */
    private $contact;

    /**
     * @var QuipuApiNumeration
     */
    private $num_series;

    public function __construct(QuipuApiConnection $api_connection)
    {
        parent::__construct($api_connection);

        // Set Endpoint
        $this->setEndpoint('invoices');
    }

    public function setContact(QuipuApiContact $contact)
    {
        $this->contact = $contact;
    }

    public function setNumeration(QuipuApiNumeration $num_series)
    {
        $this->num_series = $num_series;
    }

    public function createInvoice($order)
    {
        $contact_id = $this->contact->getId();
        $num_series_id = $this->num_series->getId();

        if (empty($contact_id)) {
            throw new Exception('Create: no contact id set.');
        }

        if (empty($order['issue_date'])) {
            throw new Exception('Create: no invoice issue date passed.');
        }

        if (empty($order['items'])) {
            throw new Exception('Create: no invoice items passed.');
        }

        $postData = array(
            'data' => array(
                'type' => 'invoices',
                'attributes' => array(
                    'kind' => 'income',
                                'number' => "$order[number]",
                    'issue_date' => "$order[issue_date]",
                    'paid_at' => "$order[issue_date]",
                    'payment_method' => "$order[payment_method]",
                ),
                'relationships' => array(
                    'contact' => array(
                        'data' => array(
                            'id' => "$contact_id",
                            'type' => 'contacts',
                        ),
                    ),
                ),
            ),
        );

        if (!empty($num_series_id)) {
            $postData['data']['relationships']['numeration']['data']['id'] = "$num_series_id";
            $postData['data']['relationships']['numeration']['data']['type'] = 'numbering_series';
        }

        foreach ($order['items'] as $value) {
            $item = array(
                        'type' => 'book_entry_items',
                        'attributes' => array(
                            'concept' => "$value[product]",
                            'unitary_amount' => "$value[cost]",
                            'quantity' => "$value[quantity]",
                            'vat_percent' => "$value[vat_per]",
                            'retention_percent' => '0',
                        ),
                    );

            $postData['data']['relationships']['items']['data'][] = $item;
        }

        try {
            $this->createRequest($postData);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function refundInvoice($refund)
    {
        $num_series_id = $this->num_series->getId();

        if (empty($refund['invoice_id'])) {
            throw new Exception('Refund: no invoice id passed.');
        }

        if (empty($refund['refund_date'])) {
            throw new Exception('Refund: no invoice issue date passed.');
        }

        $postData = array(
            'data' => array(
                'type' => 'invoices',
                'attributes' => array(
                                'issue_date' => "$refund[issue_date]",
                    'paid_at' => "$refund[refund_date]",
                                'number' => "$refund[number]",
                ),
                'relationships' => array(
                    'amended_invoice' => array(
                        'data' => array(
                            'id' => "$refund[invoice_id]",
                            'type' => 'invoices',
                        ),
                    ),
                ),
            ),
        );

        if (!empty($num_series_id)) {
            $postData['data']['relationships']['numeration']['data']['id'] = "$num_series_id";
            $postData['data']['relationships']['numeration']['data']['type'] = 'numbering_series';
        }

        if (isset($refund['items'])) {
            foreach ($refund['items'] as $value) {
                $item = array(
                            'type' => 'book_entry_items',
                            'attributes' => array(
                                'concept' => "$value[product]",
                                'unitary_amount' => "$value[cost]",
                                'quantity' => "$value[quantity]",
                                'vat_percent' => "$value[vat_per]",
                            ),
                        );

                $postData['data']['relationships']['items']['data'][] = $item;
            }
        }

        try {
            $this->createRequest($postData);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
