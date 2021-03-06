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

class QuipuApiNumeration extends QuipuApi
{
    public function __construct(QuipuApiConnection $api_connection)
    {
        parent::__construct($api_connection);

        // Set Endpoint
        $this->setEndpoint('numbering_series');
    }

    public function createSeries($prefix, $amending = false)
    {
        if (empty($prefix)) {
            throw new Exception('Create: passed prefix variable is empty.');
        }

        try {
            // Try to fetch existing prefix
            $this->getSeries($prefix, $amending);
            $id = $this->getId();
            if (empty($id)) {
                $postData = array(
                    'data' => array(
                        'type' => 'numbering_series',
                        'attributes' => array(
                            'prefix' => "$prefix",
                            'applicable_to' => 'invoices',
                            'amending' => $amending,
                            'default' => false,
                        ),
                    ),
                );

                $this->createRequest($postData);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createRefundSeries($prefix)
    {
        try {
            $this->createSeries($prefix, true);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getSeries($prefix, $amending)
    {
        if (empty($prefix)) {
            throw new Exception('Get: passed prefix variable is empty.');
        }

        try {
            $this->getFilterRequest("?filter[prefix]=$prefix&filter[amending]=$amending");
        } catch (Exception $e) {
            throw $e;
        }
    }
}
