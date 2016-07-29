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
}*/ // Exit if accessed directly
if (!class_exists('QuipuApiConnection')) {
    include_once 'class-quipu-api-connection.php';
}
abstract class QuipuApi
{
    /**
     * The request endpoint.
     *
     * @var string
     */
    private $endpoint = '';
    /**
     * The query string.
     *
     * @var string
     */
    private $query = array();
    /**
     * The request response.
     *
     * @var array
     */
    protected $response = null;
    /**
     * @var QuipuApiConnection
     */
    private $api_connection;
    /**
     * @var Integrater
     */
    protected $id = '';
    /**
     * QuipuApi constructor.
     *
     * @param string $api_key, $api_secret
     */
    public function __construct(QuipuApiConnection $api_connection)
    {
        $this->api_connection = $api_connection;
    }
    /**
     * Method to set id.
     *
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * Get the id.
     *
     * @return $id
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Method to set endpoint.
     *
     * @param $endpoint
     */
    protected function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }
    /**
     * Get the endpoint.
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return $this->endpoint;
    }
    /**
     * @return string
     */
    protected function getQuery()
    {
        return $this->query;
    }
    /**
     * @param string $query
     */
    protected function setQuery($query)
    {
        $this->query = $query;
    }
    /**
     * Get response.
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }
    /**
     * Clear the response.
     *
     * @return bool
     */
    private function clearResponse()
    {
        $this->response = null;

        return true;
    }
    public function createRequest($post_data)
    {
        try {
            $this->response = $this->api_connection->postRequest($this->endpoint, $post_data);
            if (isset($this->response['data']['id'])) {
                $this->setId($this->response['data']['id']);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getRequest()
    {
        try {
            $this->response = $this->api_connection->getRequest($this->endpoint);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getFilterRequest($query_string)
    {
        try {
            $this->response = $this->api_connection->getRequest($this->endpoint.$query_string);
            if (isset($this->response['data'][0]['id'])) {
                $this->setId($this->response['data'][0]['id']);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}
