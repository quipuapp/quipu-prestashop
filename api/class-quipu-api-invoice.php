<?php
/**
* 2007-2015 PrestaShop
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

if ( ! class_exists( 'Quipu_Api' ) ) {
	include_once( 'class-quipu-api.php' );
}
/*
if ( ! class_exists( 'Quipu_Api_Numeration' ) ) {
	include_once( 'quipu-api/class-quipu-api-numeration.php' );
}

if ( ! class_exists( 'Quipu_Api_Contact' ) ) {
	include_once( 'quipu-api/class-quipu-api-contact.php' );
}
*/

class Quipu_Api_Invoice extends Quipu_Api {

	/**
	 * @var Quipu_Api_Contact
	 */
	private $contact;

	/**
	 * @var Quipu_Api_Numeration
	 */
	private $num_series;

	public function __construct( Quipu_Api_Connection $api_connection) {
		parent::__construct( $api_connection);

		// Set Endpoint
		$this->set_endpoint( 'invoices' );
	}

	public function set_contact(Quipu_Api_Contact $contact) {
		$this->contact = $contact;
	}

	public function set_numeration(Quipu_Api_Numeration $num_series) {
		$this->num_series = $num_series;
	}

	public function create_invoice($order) {
		$contact_id = $this->contact->get_id();
		$num_series_id = $this->num_series->get_id();

		if(empty($contact_id)){
			throw new Exception('Create: no contact id set.');
		}

		if(empty($order['issue_date'])){
			throw new Exception('Create: no invoice issue date passed.');
		}

		if(empty($order['items'])){
			throw new Exception('Create: no invoice items passed.');
		}
		
		$postData = array(
		    "data" => array(
		    	"type" => "invoices",
		    	"attributes" => array(
		    		"kind" => "income",
                                "number" => "$order[number]",
		    		"issue_date" => "$order[issue_date]",
		    		"paid_at" => "$order[issue_date]",
		            "payment_method" => "$order[payment_method]"
				),
				"relationships" => array(
					"contact" => array(
						"data" => array(
							"id" => "$contact_id",
							"type" => "contacts"						
						)
					)
				)
		    )
		);

		if(!empty($num_series_id)){
			$postData["data"]["relationships"]["numeration"]["data"]["id"] = "$num_series_id";
			$postData["data"]["relationships"]["numeration"]["data"]["type"] = "numbering_series";
		}

		foreach ($order['items'] as $value) {
			$item = array( 
						"type" => "book_entry_items",
						"attributes" => array(
				    		"concept" => "$value[product]",
				    		"unitary_amount" => "$value[cost]",
				            "quantity" => "$value[quantity]",
				            "vat_percent" => "$value[vat_per]",
				            "retention_percent" => "0"
						)
					);

			$postData["data"]["relationships"]["items"]["data"][] = $item;
		}

		try {
			$this->create_request($postData);
		} catch (Exception $e) {
			throw $e;
		} 
		
	}

	public function refund_invoice($refund)	{
		$num_series_id = $this->num_series->get_id();

		if(empty($refund['invoice_id'])){
			throw new Exception('Refund: no invoice id passed.');
		}

		if(empty($refund['refund_date'])){
			throw new Exception('Refund: no invoice issue date passed.');
		}

		$postData = array(
		    "data" => array(
		    	"type" => "invoices",
		    	"attributes" => array(
                                "issue_date" => "$refund[issue_date]",
		    		"paid_at" => "$refund[refund_date]",
                                "number" => "$refund[number]",
				),
				"relationships" => array(
					"amended_invoice" => array(
						"data" => array(
							"id" => "$refund[invoice_id]",
							"type" => "invoices"						
						)
					)
				)
		    )
		);

		if(!empty($num_series_id)){
			$postData["data"]["relationships"]["numeration"]["data"]["id"] = "$num_series_id";
			$postData["data"]["relationships"]["numeration"]["data"]["type"] = "numbering_series";
		}

		if(isset($refund['items'])) {
			foreach ($refund['items'] as $value) {
				$item = array( 
							"type" => "book_entry_items",
							"attributes" => array(
					    		"concept" => "$value[product]",
					    		"unitary_amount" => "$value[cost]",
					            "quantity" => "$value[quantity]",
					            "vat_percent" => "$value[vat_per]",
							)
						);

				$postData["data"]["relationships"]["items"]["data"][] = $item;
			}
		}		

		try {
			$this->create_request($postData);
		} catch (Exception $e) {
			throw $e;
		}		
	}

}
