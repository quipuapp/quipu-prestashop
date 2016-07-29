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
if (!defined('_PS_VERSION_')) {
    exit;
}

class quipu extends Module
{
    protected $output = '';
    public $api_connexion;

    public function __construct()
    {
        $this->name = 'quipu';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.0';
        $this->author = 'Jose Aguilar';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '3ece0f6a7d9810e949e3664e8b3b804b';

        parent::__construct();

        $this->displayName = $this->l('Quipu');
        $this->description = $this->l('Quipu accounting integration with PrestaShop.');

        include_once dirname(__FILE__).'/api/class-quipu-api-connection.php';
        include_once dirname(__FILE__).'/api/class-quipu-api.php';
        include_once dirname(__FILE__).'/api/class-quipu-api-numeration.php';
        include_once dirname(__FILE__).'/api/class-quipu-api-contact.php';
        include_once dirname(__FILE__).'/api/class-quipu-api-invoice.php';
    }

    public function install()
    {
        Configuration::updateValue('QUIPU_API_ID', '');
        Configuration::updateValue('QUIPU_API_SECRET', '');
        Configuration::updateValue('QUIPU_ORDER_STATE', 2);
        Configuration::updateValue('QUIPU_SYNCHRONIZATION', 0);

        return parent::install() &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('actionOrderSlipAdd');
    }

    public function uninstall()
    {
        Configuration::deleteByName('QUIPU_API_ID');
        Configuration::deleteByName('QUIPU_API_SECRET');
        Configuration::deleteByName('QUIPU_ORDER_STATE');
        Configuration::deleteByName('QUIPU_INVOICE_PREFIX');
        Configuration::deleteByName('QUIPU_SYNCHRONIZATION');

        return parent::uninstall();
    }

    protected function postProcess()
    {
        if (Tools::isSubmit('submitIntegrationApi')) {
            Configuration::updateValue('QUIPU_API_ID', Tools::getValue('QUIPU_API_ID'));
            Configuration::updateValue('QUIPU_API_SECRET', Tools::getValue('QUIPU_API_SECRET'));

            //$this->api_connexion = Quipu_Api_Connection::get_instance(Configuration::get('QUIPU_API_ID'), Configuration::get('QUIPU_API_SECRET'));

            //if ($this->api_connexion->get_response())
                $this->output .= $this->displayConfirmation($this->l('Settings updated ok.'));
            /*else
                $this->output .= $this->displayError($this->l('Error API Connection.'));*/
        }

        if (Tools::isSubmit('submitSettings')) {
            Configuration::updateValue('QUIPU_ORDER_STATE', Tools::getValue('QUIPU_ORDER_STATE'));
            $this->output .= $this->displayConfirmation($this->l('Settings updated ok.'));
        }

        if (Tools::isSubmit('submitSynchronization')) {
            $this->api_connexion = Quipu_Api_Connection::get_instance(Configuration::get('QUIPU_API_ID'), Configuration::get('QUIPU_API_SECRET'));
            //$orders = Order::getOrdersIdByDate('1999-01-01', date('Y-m-d'));
            $order_invoices = OrderInvoice::getByDateInterval('1999-01-01', date('Y-m-d'));
            if ($order_invoices) {
                foreach ($order_invoices as $order_invoice) {
                    $order = new Order($order_invoice->id_order);
                    $this->create_quipu_invoice($order);
                }
            }

            $order_slips = OrderSlip::getSlipsIdByDate('1999-01-01', date('Y-m-d'));
            if ($order_slips) {
                foreach ($order_slips as $id_order_slip) {
                    $order_slip = new OrderSlip($id_order_slip);
                    $this->create_quipu_refund($order_slip);
                }
            }

            Configuration::updateValue('QUIPU_SYNCHRONIZATION', 1);
            $this->output .= $this->displayConfirmation($this->l('Synchronization done.'));
        }
    }

    public function getContent()
    {
        $this->postProcess();

        if (Configuration::get('QUIPU_API_ID') != '' && Configuration::get('QUIPU_API_SECRET') != '') {
            $this->api_connexion = Quipu_Api_Connection::get_instance(Configuration::get('QUIPU_API_ID'), Configuration::get('QUIPU_API_SECRET'));
        }

        $this->output .= $this->displayInformation();
        $this->output .= $this->displayFormIntegrationApi();

        if ($this->api_connexion) {
            $this->output .= $this->displayFormSettings();
            $this->output .= $this->displayFormSynchronization();
        }

        return $this->output;
    }

    private function displayInformation()
    {
        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'name' => $this->displayName,
            'version' => $this->version,
            'description' => $this->description,
            'iso_code' => $this->context->language->iso_code,
        ));

        $this->output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/information.tpl');
    }

    private function displayFormIntegrationApi()
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language) {
            $languages[$k]['is_default'] = (int) $language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitIntegrationApi';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false,
            'legend' => array(
                'title' => $this->l('INTEGRATION WITH THE API'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('API key'),
                    'name' => 'QUIPU_API_ID',
                    'desc' => $this->l('You can find this information on your Quipu account under: Integrations -> Settings -> API.'),
                    'required' => true,
                    'lang' => false,
                    'col' => 5,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('API Secret'),
                    'name' => 'QUIPU_API_SECRET',
                    'desc' => $this->l('You can also find this information on your Quipu account under: Integrations -> Settings -> API'),
                    'required' => true,
                    'lang' => false,
                    'col' => 5,
                ),
            ),
            'submit' => array(
                'name' => 'submitIntegrationApi',
                'title' => $this->l('Save'),
            ),
        );

        $helper->fields_value['QUIPU_API_ID'] = Configuration::get('QUIPU_API_ID');
        $helper->fields_value['QUIPU_API_SECRET'] = Configuration::get('QUIPU_API_SECRET');

        return $helper->generateForm($this->fields_form);
    }

    private function displayFormSettings()
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language) {
            $languages[$k]['is_default'] = (int) $language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSettings';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false,
            'legend' => array(
                'title' => $this->l('GENERAL CONFIGURATION'),
            ),
            'input' => array(
                array(
          'type' => 'select',
          'label' => $this->l('Order Status'),
          'name' => 'QUIPU_ORDER_STATE',
                  'desc' => $this->l('Select the status of the order that Quipu will create an invoice for.'),
          'required' => false,
          'options' => array(
            'query' => OrderState::getOrderStates($this->context->language->id),
            'id' => 'id_order_state',
            'name' => 'name',
          ),
        ),
            ),
            'submit' => array(
                'name' => 'submitSettings',
                'title' => $this->l('Save'),
            ),
        );

        $helper->fields_value['QUIPU_ORDER_STATE'] = Configuration::get('QUIPU_ORDER_STATE');

        return $helper->generateForm($this->fields_form);
    }

    public function displayFormSynchronization()
    {
        //Configuration::updateValue('QUIPU_SYNCHRONIZATION', 1);
        $this->context->smarty->assign(array(
            'synchronization' => Configuration::get('QUIPU_SYNCHRONIZATION'),
            'url_form' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
        ));

        $this->output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/synchronization.tpl');
    }

    public function create_quipu_invoice($order)
    {
        $customer = new Customer($order->id_customer);
        //$address_delivery = new Address($order->id_address_delivery);
        $address_invoice = new Address($order->id_address_invoice);

        $quipu_num = new Quipu_Api_Numeration($this->api_connexion);
        //$quipu_num->create_series($order->invoice_number);
        $quipu_contact = new Quipu_Api_Contact($this->api_connexion);

        $data_contact = array(
            'name' => $customer->firstname.' '.$customer->lastname,
            'tax_id' => $address_invoice->dni,
            'phone' => $address_invoice->phone,
            'email' => $customer->email,
            'address' => $address_invoice->address1,
            'town' => $address_invoice->city,
            'zip_code' => $address_invoice->postcode,
            'country_code' => Tools::strtolower(Country::getIsoById($address_invoice->id_country)),
        );

        $quipu_contact->create_contact($data_contact);

        $quipu_invoice = new Quipu_Api_Invoice($this->api_connexion);

        switch ($order->module) {
            case 'cod':
                $quipu_payment_method = 'cash';
                break;
            case 'cheque':
                $quipu_payment_method = 'check';
                break;
            case 'paypal':
                $quipu_payment_method = 'paypal';
                break;
            case 'bankwire':
                $quipu_payment_method = 'bank_transfer';
                break;
            case 'redsys':
                $quipu_payment_method = 'bank_card';
                break;
            default:
                $quipu_payment_method = 'bank_card';
                break;
        }

        $ps_order_date = explode(' ', $order->date_upd);
        $ps_order_date = $ps_order_date[0];

        $invoice_prefix = Configuration::get('PS_INVOICE_PREFIX', $this->context->language->id);
        $number = $invoice_prefix.$order->invoice_number;

        $data_quipu_invoice = array(
            'number' => "$number",
            'payment_method' => "$quipu_payment_method",
            'issue_date' => "$ps_order_date",
        );

        foreach ($order->getProducts() as $value) {
            $item = array(
                'product' => "$value[product_name]",
                'cost' => "$value[unit_price_tax_excl]",
                'quantity' => "$value[product_quantity]",
                'vat_per' => "$value[tax_rate]",
            );

            $data_quipu_invoice['items'][] = $item;
        }

        if ($order->total_shipping > 0) {
            $shipping_text = $this->l('Shipping');
            //$carrier = new Carrier($order->id_carrier, Configuration::get('PS_LANG_DEFAULT'));
            $tax_rate = Tax::getCarrierTaxRate($order->id_carrier, $order->id_address_delivery);
            $item_as_shipping = array(
                'product' => "$shipping_text",
                'cost' => "$order->total_shipping_tax_excl",
                'quantity' => '1',
                'vat_per' => "$tax_rate",
            );

            $data_quipu_invoice['items'][] = $item_as_shipping;
        }

        $quipu_invoice->set_contact($quipu_contact);
        $quipu_invoice->set_numeration($quipu_num);
        $quipu_invoice->create_invoice($data_quipu_invoice);

        //save note quipu
        $quipu_invoice_id = $quipu_invoice->get_id();
        $order_invoice = new OrderInvoice($order->invoice_number);
        $order_invoice->note = $quipu_invoice_id;
        $order_invoice->update();
        //Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'order_invoice` SET `note` = "'.$quipu_invoice_id.'" WHERE id_order_invoice = '.(int)$order->invoice_number);
    }

    public function create_quipu_refund($order_slip)
    {
        $id_order = $order_slip->id_order;
        $order = new Order($id_order);
        $order_date = explode(' ', $order->date_add);
        $order_date = $order_date[0];
        $order_date = date($order_date, time());
        $order_invoice = new OrderInvoice($order->invoice_number);
        $quipu_invoice_id = (int) $order_invoice->note;

        //$id_order_slip = $this->getSlipByOrder($id_order);

        /*if (count($id_order_slip) > 1)
            $id_order_slip = $id_order_slip[];*/

        if (!empty($quipu_invoice_id)) {
            //$this->api_connexion = Quipu_Api_Connection::get_instance(Configuration::get('QUIPU_API_ID'), Configuration::get('QUIPU_API_SECRET'));
            $quipu_num = new Quipu_Api_Numeration($this->api_connexion);
            //$quipu_num->create_refund_series($this->refund_num_series);
            $quipu_invoice = new Quipu_Api_Invoice($this->api_connexion);
            $quipu_invoice->set_numeration($quipu_num);
            $id_order_slip = 'R-'.$order_slip->id;
            //$refund_date = date('Y-m-d', time());
            $order_slip_date = explode(' ', $order_slip->date_add);
            $order_slip_date = $order_slip_date[0];
            $refund_date = date($order_slip_date, time());
            $refund = array(
                'number' => "$id_order_slip",
                'invoice_id' => "$quipu_invoice_id",
                'issue_date' => "$refund_date",
                'refund_date' => "$refund_date",
            );

            //d($refund);

            // Partial refund if the order amount is NOT the same as the refund amount!
            if ($order->getTotalProductsWithTaxes() > Tools::ps_round($order_slip->total_products_tax_incl, 2)) {
                $order_slip_details = OrderSlip::getOrdersSlipProducts($order_slip->id, $order);
                foreach ($order_slip_details as $value) {
                    $order_detail = new OrderDetail($value['id_order_detail']);
                    $product = new Product($order_detail->product_id, false, $this->context->language->id);
                    $unit_price_tax_excl = $value['total_price_tax_excl'] * -1;
                    $tax_rate = Tax::getProductTaxRate($order_detail->product_id);
                    $quantity = $value['product_quantity'];

                    $item = array(
                        'product' => "$product->name",
                        'cost' => "$unit_price_tax_excl",
                        'quantity' => "$quantity",
                        'vat_per' => "$tax_rate",
                    );

                    $refund['items'][] = $item;
                }

                /*if (Tools::getValue('partialRefundShippingCost') > 0) {
                    $total_shipping_tax_incl = Tools::getValue('partialRefundShippingCost');
                    $carrier = new Carrier($order->id_carrier, Configuration::get('PS_LANG_DEFAULT'));
                    $tax_rate = Tax::getCarrierTaxRate($order->id_carrier);
                    $total_shipping_tax_excl = $total_shipping_tax_incl / (1 + ($tax_rate / 100));
                    $total_shipping_tax_excl = $total_shipping_tax_excl * -1;

                    $item_as_shipping = array(
                        "product" => "$carrier->name",
                        "cost" => "$total_shipping_tax_excl",
                        "quantity" => "1",
                        "vat_per" => "$tax_rate"
                    );

                    $refund['items'][] = $item_as_shipping;
                }*/
            } else {
                //rembolso estandard
                $order_products = $order->getProducts();
                foreach ($order_products as $product) {
                    $quantity = $product['product_quantity'];
                    $tax_rate = $product['tax_rate'];
                    $unit_price_tax_excl = $product['unit_price_tax_excl'] * -1;
                    $refund_name = $product['product_name'];

                    $item = array(
                        'product' => "$refund_name",
                        'cost' => "$unit_price_tax_excl",
                        'quantity' => "$quantity",
                        'vat_per' => "$tax_rate",
                    );

                    $refund['items'][] = $item;
                }

                if ($order_slip->total_shipping_tax_excl > 0) {
                    //$carrier = new Carrier($order->id_carrier, Configuration::get('PS_LANG_DEFAULT'));
                    $tax_rate = Tax::getCarrierTaxRate($order->id_carrier, $order->id_address_delivery);
                    $total_shipping_tax_excl = $order_slip->total_shipping_tax_excl * -1;
                    $shipping_text = $this->l('Shipping');
                    $item_as_shipping = array(
                        'product' => "$shipping_text",
                        'cost' => "$total_shipping_tax_excl",
                        'quantity' => '1',
                        'vat_per' => "$tax_rate",
                    );

                    $refund['items'][] = $item_as_shipping;
                }
            }

            $quipu_invoice->refund_invoice($refund);
        }
    }

    public function order_competed($params)
    {
        if (Validate::isLoadedObject($params['newOrderStatus'])) {
            $id_order = $params['id_order'];
        } else {
            $id_order = $params['order']->id;
        }

        $this->api_connexion = Quipu_Api_Connection::get_instance(Configuration::get('QUIPU_API_ID'), Configuration::get('QUIPU_API_SECRET'));
        $order = new Order($id_order);
        $this->create_quipu_invoice($order);
    }

    public function order_refund($params)
    {
        //d($_POST);
        $id_order = $params['order']->id;
        $order = new Order($id_order);
        $order_date = explode(' ', $order->date_add);
        $order_date = $order_date[0];
        $order_date = date($order_date, time());
        $order_invoice = new OrderInvoice($order->invoice_number);
        $quipu_invoice_id = (int) $order_invoice->note;

        if (!empty($quipu_invoice_id)) {
            $this->api_connexion = Quipu_Api_Connection::get_instance(Configuration::get('QUIPU_API_ID'), Configuration::get('QUIPU_API_SECRET'));
            $quipu_num = new Quipu_Api_Numeration($this->api_connexion);
            //$quipu_num->create_refund_series($this->refund_num_series);
            $quipu_invoice = new Quipu_Api_Invoice($this->api_connexion);
            $quipu_invoice->set_numeration($quipu_num);
            $id_order_slip = 'R-'.$this->getLastSlipByOrder($id_order);
            /*$order_slip = new OrderSlip($this->getSlipByOrder($id_order));
            $order_slip_date = explode(' ', $order_slip->date_add);
            $order_slip_date = $order_slip_date[0];
            $refund_date = date($order_slip_date, time());*/
            $refund_date = date('Y-m-d', time());
            $refund = array(
                'number' => "$id_order_slip",
                'invoice_id' => "$quipu_invoice_id",
                'issue_date' => "$refund_date",
                'refund_date' => "$order_date",
            );

            //llega cuando devolucion parcial de 1 proudcto 50%
        /*[partialRefundProductQuantity] => Array ( [109] => 1 ) [partialRefundProduct] => Array ( [109] => 50 ) */
        //lllega cuando devolucion parcial con cantidad
        //[partialRefundProductQuantity] => Array ( [109] => 2 ) [partialRefundProduct] => Array ( [109] => )

            // Partial refund if the order amount is NOT the same as the refund amount!
            if (Tools::isSubmit('partialRefund')) {
                $partialRefundProductQuantity = Tools::getValue('partialRefundProductQuantity');
                $partialRefundProduct = Tools::getValue('partialRefundProduct');
                $order_products = $order->getProducts();
                foreach ($params['productList'] as $key => $value) {
                    $order_detail = new OrderDetail($key);
                    foreach ($order_products as $product) {
                        if ($product['id_product'] == $order_detail->product_id) {
                            if (array_key_exists($key, $partialRefundProductQuantity)) {
                                if ($partialRefundProductQuantity[$key] > 0) {
                                    $quantity = $partialRefundProductQuantity[$key];
                                    $unit_price_tax_excl = $product['unit_price_tax_excl'] * -1;
                                }
                            }

                            if (array_key_exists($key, $partialRefundProduct)) {
                                if ($partialRefundProduct[$key] > 0) {
                                    $unit_price_tax_excl = $partialRefundProduct[$key] * -1;
                                }
                            }

                            $tax_rate = $product['tax_rate'];
                            $refund_name = $product['product_name'];

                            $item = array(
                                'product' => "$refund_name",
                                'cost' => "$unit_price_tax_excl",
                                'quantity' => "$quantity",
                                'vat_per' => "$tax_rate",
                            );

                            $refund['items'][] = $item;
                        }
                    }
                }

                if (Tools::getValue('partialRefundShippingCost') > 0) {
                    $partialRefundShippingCost = str_replace(',', '.', Tools::getValue('partialRefundShippingCost'));
                    $total_shipping_tax_incl = (float) $partialRefundShippingCost;
                    $tax_rate = Tax::getCarrierTaxRate($order->id_carrier);
                    $total_shipping_tax_excl = $total_shipping_tax_incl / (1 + ($tax_rate / 100));
                    $total_shipping_tax_excl = $total_shipping_tax_excl * -1;
                    $shipping_text = $this->l('Shipping');
                    $item_as_shipping = array(
                        'product' => "$shipping_text",
                        'cost' => "$total_shipping_tax_excl",
                        'quantity' => '1',
                        'vat_per' => "$tax_rate",
                    );

                    $refund['items'][] = $item_as_shipping;
                }
            } else {
                //rembolso estandard
                $order_products = $order->getProducts();
                foreach ($order_products as $product) {
                    $quantity = $product['product_quantity'];
                    $tax_rate = $product['tax_rate'];
                    $unit_price_tax_excl = $product['unit_price_tax_excl'] * -1;
                    $refund_name = $product['product_name'];

                    $item = array(
                        'product' => "$refund_name",
                        'cost' => "$unit_price_tax_excl",
                        'quantity' => "$quantity",
                        'vat_per' => "$tax_rate",
                    );

                    $refund['items'][] = $item;
                }

                if (Tools::getValue('shippingBack') == 'on') {
                    //$carrier = new Carrier($order->id_carrier, Configuration::get('PS_LANG_DEFAULT'));
                    $tax_rate = Tax::getCarrierTaxRate($order->id_carrier);
                    $total_shipping_tax_excl = $order->total_shipping_tax_excl * -1;
                    $shipping_text = $this->l('Shipping');
                    $item_as_shipping = array(
                        'product' => "$shipping_text",
                        'cost' => "$total_shipping_tax_excl",
                        'quantity' => '1',
                        'vat_per' => "$tax_rate",
                    );

                    $refund['items'][] = $item_as_shipping;
                }
            }

            $quipu_invoice->refund_invoice($refund);
        }
    }

    public static function getSlipByOrder($id_order)
    {
        return Db::getInstance()->getValue('SELECT id_order_slip FROM '._DB_PREFIX_.'order_slip WHERE id_order ='.(int) $id_order);
    }

    public static function getLastSlipByOrder($id_order)
    {
        return Db::getInstance()->getValue('SELECT max(id_order_slip) FROM '._DB_PREFIX_.'order_slip WHERE id_order ='.(int) $id_order);
    }

    public function hookActionValidateOrder($params)
    {
        if (Configuration::get('QUIPU_ORDER_STATE') == $params['order']->current_state) {
            $this->order_competed($params);
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if (Configuration::get('QUIPU_ORDER_STATE') == $params['newOrderStatus']->id) {
            $this->order_competed($params);
        }
    }

    public function hookActionOrderSlipAdd($params)
    {
        $this->order_refund($params);
    }
}
