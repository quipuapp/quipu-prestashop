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

if (!class_exists('Quipu_Api')) {
    include_once 'class-quipu-api.php';
}

class Quipu_Api_Contact extends Quipu_Api
{
    public function __construct(Quipu_Api_Connection $api_connection)
    {
        parent::__construct($api_connection);

        // Set Endpoint
        $this->set_endpoint('contacts');
    }

    private function __create_contact($contact)
    {
        if (empty($contact['name'])) {
            throw new Exception('Create: no contact name passed.');
        }

        try {
            $postData = array(
                'data' => array(
                    'type' => 'contacts',
                    'attributes' => array(
                        'name' => "$contact[name]",
                        'tax_id' => "$contact[tax_id]",
                        'phone' => "$contact[phone]",
                        'email' => "$contact[email]",
                        'address' => "$contact[address]",
                        'town' => "$contact[town]",
                        'zip_code' => "$contact[zip_code]",
                        'country_code' => "$contact[country_code]",
                    ),
                ),
            );

            $this->create_request($postData);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function create_contact($contact)
    {
        try {
            if ($contact['tax_id']) {
                //d($contact['tax_id']);
                $this->get_contact($contact['tax_id']);

                $id = $this->get_id();
                if (empty($id)) {
                    $this->__create_contact($contact);
                }
            } else {
                $this->__create_contact($contact);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function get_contact($tax_id)
    {
        if (empty($tax_id)) {
            throw new Exception('Get: no tax id passed.');
        }

        return $this->get_filter_request("?filter[tax_id]=$tax_id");
    }
}
