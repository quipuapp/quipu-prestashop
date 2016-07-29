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

class QuipuApiContact extends QuipuApi
{
    public function __construct(QuipuApiConnection $api_connection)
    {
        parent::__construct($api_connection);

        // Set Endpoint
        $this->setEndpoint('contacts');
    }

    private function __createContact($contact)
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

            $this->createRequest($postData);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createContact($contact)
    {
        try {
            if ($contact['tax_id']) {
                //d($contact['tax_id']);
                $this->getContact($contact['tax_id']);

                $id = $this->getId();
                if (empty($id)) {
                    $this->__createContact($contact);
                }
            } else {
                $this->__createContact($contact);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getContact($tax_id)
    {
        if (empty($tax_id)) {
            throw new Exception('Get: no tax id passed.');
        }

        return $this->getFilterRequest("?filter[tax_id]=$tax_id");
    }
}
