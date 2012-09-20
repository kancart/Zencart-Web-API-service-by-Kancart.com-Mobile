<?php

/**
 * KanCart
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@kancart.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade KanCart to newer
 * versions in the future. If you wish to customize KanCart for your
 * needs please refer to http://www.kancart.com for more information.
 *
 * @copyright  Copyright (c) 2011 kancart.com (http://www.kancart.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
if (!defined('ALLOW')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

include_once(DIR_WS_MODULES . 'payment/paypal/paypal_functions.php');

class PayPalWPS {

    /**
     * string representing the payment method
     *
     * @var string
     */
    var $code;

    /**
     * $title is the displayed name for this payment method
     *
     * @var string
     */
    var $title;

    /**
     * $description is a soft name for this payment method
     *
     * @var string
     */
    var $description;

    /**
     * $enabled determines whether this module shows or not... in catalog.
     *
     * @var boolean
     */
    var $enabled;
    var $form_action_url;
    var $pdtData;
    var $transaction_amount;
    var $totalsum;
    var $transaction_currency;
    var $order_status;
    var $transaction_id;
    var $_check;

    /**
     * constructor
     *
     * @param int $paypal_ipn_id
     * @return paypal
     */
    function PayPalWPS() {
        global $order;
        $this->code = 'paypal';
        $this->codeVersion = '1.3.9';
        $this->title = MODULE_PAYMENT_PAYPAL_TEXT_CATALOG_TITLE; // Payment Module title in Catalog
        $this->description = MODULE_PAYMENT_PAYPAL_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_PAYPAL_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_PAYPAL_STATUS == 'True') ? true : false);
        if ((int) MODULE_PAYMENT_PAYPAL_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PAYPAL_ORDER_STATUS_ID;
        }
        if (is_object($order))
            $this->update_status();
        $this->form_action_url = 'https://' . MODULE_PAYMENT_PAYPAL_HANDLER;
        if (defined('MODULE_PAYMENT_PAYPAL_HANDLER')) {
            KCLogger::Log('defined');
        } else {
            KCLogger::Log('undefined');
        }
    }

    /**
     * calculate zone matches and flag settings to determine whether this module should display to customers or not
     *
     */
    function update_status() {
        global $order, $db;

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_PAYPAL_ZONE > 0)) {
            $check_flag = false;
            $check_query = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYPAL_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
            while (!$check_query->EOF) {
                if ($check_query->fields['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check_query->fields['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
                $check_query->MoveNext();
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    /**
     * Displays payment method name along with Credit Card Information Submission Fields (if any) on the Checkout Payment Page
     *
     * @return array
     */
    function selection() {
        return array('id' => $this->code,
            'module' => MODULE_PAYMENT_PAYPAL_TEXT_CATALOG_LOGO,
            'icon' => MODULE_PAYMENT_PAYPAL_TEXT_CATALOG_LOGO
        );
    }

    /**
     * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
     * This sends the data to the payment gateway for processing.
     * (These are hidden fields on the checkout confirmation page)
     * 生成表单信息
     * @return string
     */
    function getParams() {
        global $db, $order, $currencies, $currency;
        $options = array();
        $optionsCore = array();
        $optionsPhone = array();
        $optionsShip = array();
        $optionsLineItems = array();
        $optionsAggregate = array();
        $optionsTrans = array();
        $fieldsArray = array();


        // save the session stuff permanently in case paypal loses the session
        $_SESSION['ppipn_key_to_remove'] = session_id();
        $db->Execute("delete from " . TABLE_PAYPAL_SESSION . " where session_id = '" . zen_db_input($_SESSION['ppipn_key_to_remove']) . "'");

        $sql = "insert into " . TABLE_PAYPAL_SESSION . " (session_id, saved_session, expiry) values (
            '" . zen_db_input($_SESSION['ppipn_key_to_remove']) . "',
            '" . base64_encode(serialize($_SESSION)) . "',
            '" . (time() + (1 * 60 * 60 * 24 * 2)) . "')";

        $db->Execute($sql);

        $my_currency = select_pp_currency();
        $this->transaction_currency = $my_currency;   // ============================
        // 订单总价
        $order->info['total'] = zen_round($order->info['total'], 2);
        $this->totalsum = $order->info['total'];
        $this->transaction_amount = ($this->totalsum * $currencies->get_value($my_currency));    // ============================

        $telephone = preg_replace('/\D/', '', $order->customer['telephone']);
        if ($telephone != '') {
            $optionsPhone['H_PhoneNumber'] = $telephone;
            if (in_array($order->customer['country']['iso_code_2'], array('US', 'CA'))) {
                $optionsPhone['night_phone_a'] = substr($telephone, 0, 3);
                $optionsPhone['night_phone_b'] = substr($telephone, 3, 3);
                $optionsPhone['night_phone_c'] = substr($telephone, 6, 4);
                $optionsPhone['day_phone_a'] = substr($telephone, 0, 3);
                $optionsPhone['day_phone_b'] = substr($telephone, 3, 3);
                $optionsPhone['day_phone_c'] = substr($telephone, 6, 4);
            } else {
                $optionsPhone['night_phone_b'] = $telephone;
                $optionsPhone['day_phone_b'] = $telephone;
            }
        }

        $optionsCore = array(
            'lc' => $this->getLanguageCode(),
//                   'lc' => $order->customer['country']['iso_code_2'],
            'charset' => CHARSET,
            'page_style' => MODULE_PAYMENT_PAYPAL_PAGE_STYLE,
            'custom' => zen_session_name() . '=' . zen_session_id(),
            'business' => MODULE_PAYMENT_PAYPAL_BUSINESS_ID,
            'return' => zen_href_link(FILENAME_CHECKOUT_PROCESS, 'referer=paypal', 'SSL'), // 支付完成后的返回URL
            'cancel_return' => zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'), // 取消支付的返回URL
            'shopping_url' => zen_href_link(FILENAME_SHOPPING_CART, '', 'SSL'), // 购物车URL
            'notify_url' => zen_href_link('ipn_main_handler.php', '', 'SSL', false, false, true), // IPN 的URL
            'redirect_cmd' => '_xclick', 'rm' => 2, 'bn' => 'Kancart_SP_WPS', 'mrb' => 'R-6C7952342H795591R', 'pal' => '9E82WJBKKGPLQ',
        );
        $optionsCust = array(
            'first_name' => replace_accents($order->customer['firstname']),
            'last_name' => replace_accents($order->customer['lastname']),
            'address1' => replace_accents($order->customer['street_address']),
            'city' => replace_accents($order->customer['city']),
            'state' => zen_get_zone_code($order->customer['country']['id'], $order->customer['zone_id'], $order->customer['state']),
            'zip' => $order->customer['postcode'],
            'country' => $order->customer['country']['iso_code_2'],
            'email' => $order->customer['email_address'],
        );
        // address line 2 is optional
        if ($order->customer['suburb'] != '')
            $optionsCust['address2'] = $order->customer['suburb'];
        // different format for Japanese address layout:
        if ($order->customer['country']['iso_code_2'] == 'JP')
            $optionsCust['zip'] = substr($order->customer['postcode'], 0, 3) . '-' . substr($order->customer['postcode'], 3);
        if (MODULE_PAYMENT_PAYPAL_ADDRESS_REQUIRED == 2) {
            $optionsCust = array(
                'first_name' => replace_accents($order->delivery['firstname'] != '' ? $order->delivery['firstname'] : $order->billing['firstname']),
                'last_name' => replace_accents($order->delivery['lastname'] != '' ? $order->delivery['lastname'] : $order->billing['lastname']),
                'address1' => replace_accents($order->delivery['street_address'] != '' ? $order->delivery['street_address'] : $order->billing['street_address']),
                'city' => replace_accents($order->delivery['city'] != '' ? $order->delivery['city'] : $order->billing['city']),
                'state' => ($order->delivery['country']['id'] != '' ? zen_get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], $order->delivery['state']) : zen_get_zone_code($order->billing['country']['id'], $order->billing['zone_id'], $order->billing['state'])),
                'zip' => ($order->delivery['postcode'] != '' ? $order->delivery['postcode'] : $order->billing['postcode']),
                'country' => ($order->delivery['country']['title'] != '' ? $order->delivery['country']['title'] : $order->billing['country']['title']),
                'country_code' => ($order->delivery['country']['iso_code_2'] != '' ? $order->delivery['country']['iso_code_2'] : $order->billing['country']['iso_code_2']),
                'email' => $order->customer['email_address'],
            );
            if ($order->delivery['suburb'] != '')
                $optionsCust['address2'] = $order->delivery['suburb'];
            if ($order->delivery['country']['iso_code_2'] == 'JP')
                $optionsCust['zip'] = substr($order->delivery['postcode'], 0, 3) . '-' . substr($order->delivery['postcode'], 3);
        }
        $optionsShip['no_shipping'] = MODULE_PAYMENT_PAYPAL_ADDRESS_REQUIRED;
        if (MODULE_PAYMENT_PAYPAL_ADDRESS_OVERRIDE == '1')
            $optionsShip['address_override'] = MODULE_PAYMENT_PAYPAL_ADDRESS_OVERRIDE;
        // prepare cart contents details where possible
        if (MODULE_PAYMENT_PAYPAL_DETAILED_CART == 'Yes')
            $optionsLineItems = ipn_getLineItemDetails($my_currency);
        if (sizeof($optionsLineItems) > 0) {
            $optionsLineItems['cmd'] = '_cart';
//      $optionsLineItems['num_cart_items'] = sizeof($order->products);
            if (isset($optionsLineItems['shipping'])) {
                $optionsLineItems['shipping_1'] = $optionsLineItems['shipping'];
                unset($optionsLineItems['shipping']);
            }
            unset($optionsLineItems['subtotal']);
            // if line-item details couldn't be kept due to calculation mismatches or discounts etc, default to aggregate mode
            if (!isset($optionsLineItems['item_name_1']) || $optionsLineItems['creditsExist'] == TRUE)
                $optionsLineItems = array();
            //if ($optionsLineItems['amount'] != $this->transaction_amount) $optionsLineItems = array();
            // debug:
            //ipn_debug_email('Line Item Details (if blank, this means there was a data mismatch or credits applied, and thus bypassed): ' . "\n" . print_r($optionsLineItems, true));
            unset($optionsLineItems['creditsExist']);
        }
        $optionsAggregate = array(
            'cmd' => '_ext-enter',
            'item_name' => MODULE_PAYMENT_PAYPAL_PURCHASE_DESCRIPTION_TITLE,
            'item_number' => MODULE_PAYMENT_PAYPAL_PURCHASE_DESCRIPTION_ITEMNUM,
            //'num_cart_items' => sizeof($order->products),
            'amount' => number_format($this->transaction_amount, $currencies->get_decimal_places($my_currency)),
            'shipping' => '0.00',
        );
        if (MODULE_PAYMENT_PAYPAL_TAX_OVERRIDE == 'true')
            $optionsAggregate['tax'] = '0.00';
        if (MODULE_PAYMENT_PAYPAL_TAX_OVERRIDE == 'true')
            $optionsAggregate['tax_cart'] = '0.00';
        $optionsTrans = array(
            'upload' => (int) (sizeof($order->products) > 0),
            'currency_code' => $my_currency,
//                   'paypal_order_id' => $paypal_order_id,
                //'no_note' => '1',
                //'invoice' => '',
        );

        // if line-item info is invalid, use aggregate:
        if (sizeof($optionsLineItems) > 0)
            $optionsAggregate = $optionsLineItems;


        if (defined('MODULE_PAYMENT_PAYPAL_LOGO_IMAGE'))
            $optionsCore['cpp_logo_image'] = urlencode(MODULE_PAYMENT_LOGO_IMAGE);
        if (defined('MODULE_PAYMENT_PAYPAL_CART_BORDER_COLOR'))
            $optionsCore['cpp_cart_border_color'] = MODULE_PAYMENT_PAYPAL_CART_BORDER_COLOR;


        // prepare submission
        $options = array_merge($optionsCore, $optionsCust, $optionsPhone, $optionsShip, $optionsTrans, $optionsAggregate);
        //ipn_debug_email('Keys for submission: ' . print_r($options, true));
        // build the button fields
        foreach ($options as $name => $value) {
            // remove quotation marks
            $value = str_replace('"', '', $value);
            // check for invalid chars
            if (preg_match('/[^a-zA-Z_0-9]/', $name)) {
                ipn_debug_email('datacheck - ABORTING - preg_match found invalid submission key: ' . $name . ' (' . $value . ')');
                break;
            }
            // do we need special handling for & and = symbols?
            //if (strpos($value, '&') !== false || strpos($value, '=') !== false) $value = urlencode($value);

            $fieldsArray[$name] = $value;
        }

        $_SESSION['paypal_transaction_info'] = array($this->transaction_amount, $this->transaction_currency);
        return $fieldsArray;
    }

    /**
     * Determine the language to use when visiting the PayPal site
     */
    function getLanguageCode() {
        global $order;
        $lang_code = '';
        $orderISO = zen_get_countries($order->customer['country']['id'], true);
        $storeISO = zen_get_countries(STORE_COUNTRY, true);
        if (in_array(strtoupper($orderISO['countries_iso_code_2']), array('US', 'AU', 'DE', 'FR', 'IT', 'GB', 'ES', 'AT', 'BE', 'CA', 'CH', 'CN', 'NL', 'PL'))) {
            $lang_code = strtoupper($orderISO['countries_iso_code_2']);
        } elseif (in_array(strtoupper($storeISO['countries_iso_code_2']), array('US', 'AU', 'DE', 'FR', 'IT', 'GB', 'ES', 'AT', 'BE', 'CA', 'CH', 'CN', 'NL', 'PL'))) {
            $lang_code = strtoupper($storeISO['countries_iso_code_2']);
        } elseif (in_array(strtoupper($_SESSION['languages_code']), array('EN', 'US', 'AU', 'DE', 'FR', 'IT', 'GB', 'ES', 'AT', 'BE', 'CA', 'CH', 'CN', 'NL', 'PL'))) {
            $lang_code = $_SESSION['languages_code'];
            if (strtoupper($lang_code) == 'EN')
                $lang_code = 'US';
        }
        //return $orderISO['countries_iso_code_2'];
        return strtoupper($lang_code);
    }

    /**
     * Store transaction info to the order and process any results that come back from the payment gateway
     */
    function before_process() {
        global $order_total_modules, $kcResponse, $db;
        // TODO XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
        list($this->transaction_amount, $this->transaction_currency) = $_SESSION['paypal_transaction_info'];
        unset($_SESSION['paypal_transaction_info']);
        KCLogger::Log('before_process: 1');
        if (defined('MODULE_PAYMENT_PAYPAL_PDTTOKEN') && MODULE_PAYMENT_PAYPAL_PDTTOKEN != '' && isset($_POST['tx']) && $_POST['tx'] != '') {
            $pdtStatus = $this->_getPDTresults($this->transaction_amount, $this->transaction_currency, $_POST['tx']);
            KCLogger::Log('before_process: 2: ' . $pdtStatus ? 'true' : 'false');
        } else {
            $pdtStatus = false;
            KCLogger::Log('before_process: 3: ' . $pdtStatus ? 'true' : 'false');
        }
        if ($pdtStatus == false) {
            KCLogger::Log('before_process: 4');
            $_SESSION['cart']->reset(true);
            unset($_SESSION['sendto']);
            unset($_SESSION['billto']);
            unset($_SESSION['shipping']);
            unset($_SESSION['payment']);
            unset($_SESSION['comments']);
            unset($_SESSION['cot_gv']);
            $order_total_modules->clear_posts(); //ICW ADDED FOR CREDIT CLASS SYSTEM
            // 支付成功页面
// find out the last order number generated for this customer account
            $orders_query = "SELECT * FROM " . TABLE_ORDERS . "
                 WHERE customers_id = :customersID
                 ORDER BY date_purchased DESC LIMIT 1";
            $orders_query = $db->bindVars($orders_query, ':customersID', $_SESSION['customer_id'], 'integer');
            $orders = $db->Execute($orders_query);
            $orders_id = $orders->fields['orders_id'];

// use order-id generated by the actual order process
// this uses the SESSION orders_id, or if doesn't exist, grabs most recent order # for this cust (needed for paypal et al).
// Needs reworking in v1.4 for checkout-rewrite
            $zv_orders_id = (isset($_SESSION['order_number_created']) && $_SESSION['order_number_created'] >= 1) ? $_SESSION['order_number_created'] : $orders_id;
            $orders_id = $zv_orders_id;
            unset($_SESSION['order_summary']);
            unset($_SESSION['order_number_created']);
            $latestOrder = new KC_Order($orders_id);

            $returnResults = array();
            $returnResults['order_id'] = $latestOrder->order_id;
            foreach ($latestOrder->price_infos as $priceInfo) {
                if ($priceInfo->type == 'total') {
                    $returnResults['payment_total'] = $priceInfo->price;
                    break;
                }
            }
            $returnResults['currency'] = $latestOrder->currency;
            $kcResponse->DataBack($returnResults);
            // zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
        } else {
            KCLogger::Log('before_process: 5');
            // PDT was good, so delete IPN session from PayPal table -- housekeeping.
            global $db;
            $db->Execute("delete from " . TABLE_PAYPAL_SESSION . " where session_id = '" . zen_db_input($_SESSION['ppipn_key_to_remove']) . "'");
            unset($_SESSION['ppipn_key_to_remove']);
            $_SESSION['paypal_transaction_PDT_passed'] = true;
            return true;
        }
        if (isset($_POST['referer']) && $_POST['referer'] == 'paypal') {
            
        } else {
            // PAYPAL 支付被取消了
//            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
        }
    }

    /**
     * Build admin-page components
     *
     * @param int $zf_order_id
     * @return string
     */
    function admin_notification($zf_order_id) {
        global $db;
        $output = '';
        $sql = "select * from " . TABLE_PAYPAL . " where order_id = '" . (int) $zf_order_id . "' order by paypal_ipn_id DESC LIMIT 1";
        $ipn = $db->Execute($sql);
        if ($ipn->RecordCount() > 0 && file_exists(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/paypal/paypal_admin_notification.php'))
            require(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/paypal/paypal_admin_notification.php');
        return $output;
    }

    function checkoutProcess() {
        Global $credit_covers, $currencies, $language_page_directory, $template_dir, $current_page_base, $template, $order_total_modules, $order_totals, $order, $kcResponse;
        $language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';
        $current_page_base = 'checkout_process';
        KCLogger::Log('checkoutProcess 0');
        require_once DIR_WS_MODULES . zen_get_module_directory('require_languages.php');
        // 支付返回， 处理结算并下单
        KCLogger::Log('checkoutProcess 1');
        // if the customer is not logged on, redirect them to the time out page
        if (!$_SESSION['customer_id']) {
            zen_redirect(zen_href_link(FILENAME_TIME_OUT));
        } else {
            // validate customer
            if (zen_get_customer_validate_session($_SESSION['customer_id']) == false) {
                $_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_SHIPPING));
                zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
            }
        }
        KCLogger::Log('checkoutProcess 2');
        // confirm where link came from
        if (!strstr($_SERVER['HTTP_REFERER'], FILENAME_CHECKOUT_CONFIRMATION)) {
            //    zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT,'','SSL'));
        }

        // END CC SLAM PREVENTION
        KCLogger::Log('checkoutProcess 3');
        if (!isset($credit_covers))
            $credit_covers = FALSE;

        require(DIR_WS_CLASSES . 'order.php');
        $order = new order;

        // prevent 0-entry orders from being generated/spoofed
        if (sizeof($order->products) < 1) {
            // TODO 没有商品， 回到购物车
            $kcResponse->ErrorBack('0x5002', 'Your cart is empty!');
            // zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
        }
        KCLogger::Log('checkoutProcess 4');
        require(DIR_WS_CLASSES . 'order_total.php');
        $order_total_modules = new order_total;

        if (strpos($GLOBALS[$_SESSION['payment']]->code, 'paypal') !== 0) {
            $order_totals = $order_total_modules->pre_confirmation_check();
        }
        if ($credit_covers === TRUE) {
            $order->info['payment_method'] = $order->info['payment_module_code'] = '';
        }
        if (!isset($order->info['comments']) || strlen($order->info['comments']) == 0) {
            $order->info['comments'] = '';
        }
        // add kancart order comment
        $order->info['comments'] .= '\n' . isset($_REQUEST['custom_kc_comments']) ? $_REQUEST['custom_kc_comments']: '';
        $order_totals = $order_total_modules->process();
        KCLogger::Log('checkoutProcess 5');
        KCLogger::Log('checkoutProcess 5: $order->info: ' . json_encode($order->info));
        // load the before_process function from the payment modules
        $this->before_process();
        //$order->info['order_status'] = $this->order_status;
        // create the order record
        KCLogger::Log('checkoutProcess 6: $order->info: ' . json_encode($order->info));
        $insert_id = $order->create($order_totals, 2);
        KCLogger::Log('checkoutProcess 6 ' . $insert_id);
        // store the product info to the order
        $order->create_add_products($insert_id);
        $_SESSION['order_number_created'] = $insert_id;
        KCLogger::Log('checkoutProcess 7');
//send email notifications
        $order->send_order_email($insert_id, 2);
        KCLogger::Log('checkoutProcess 8');
// clear slamming protection since payment was accepted
        if (isset($_SESSION['payment_attempt']))
            unset($_SESSION['payment_attempt']);

        /**
         * Calculate order amount for display purposes on checkout-success page as well as adword campaigns etc
         * Takes the product subtotal and subtracts all credits from it
         */
        $ototal = $order_subtotal = $credits_applied = 0;
        for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
            if ($order_totals[$i]['code'] == 'ot_subtotal')
                $order_subtotal = $order_totals[$i]['value'];
            if ($$order_totals[$i]['code']->credit_class == true)
                $credits_applied += $order_totals[$i]['value'];
            if ($order_totals[$i]['code'] == 'ot_total')
                $ototal = $order_totals[$i]['value'];
            if ($order_totals[$i]['code'] == 'ot_tax')
                $otax = $order_totals[$i]['value'];
            if ($order_totals[$i]['code'] == 'ot_shipping')
                $oshipping = $order_totals[$i]['value'];
        }
        $commissionable_order = ($order_subtotal - $credits_applied);
        $commissionable_order_formatted = $currencies->format($commissionable_order);

        $returnResults = array();
        $returnResults['payment_total'] = $ototal;
        $returnResults['currency'] = $order->info['currency'];
        $returnResults['order_id'] = $insert_id;
        KCLogger::Log('checkoutProcess 9');
        // load the after_process function from the payment modules
        $this->after_process($insert_id, $order);
        KCLogger::Log('checkoutProcess 10');
        $_SESSION['cart']->reset(true);
        KCLogger::Log('checkoutProcess 11');
        // unregister session variables used during checkout
        unset($_SESSION['sendto']);
        unset($_SESSION['billto']);
        unset($_SESSION['shipping']);
        unset($_SESSION['payment']);
        unset($_SESSION['comments']);
        $order_total_modules->clear_posts(); //ICW ADDED FOR CREDIT CLASS SYSTEM
        KCLogger::Log('checkoutProcess 12');
        return $returnResults;
    }

    /**
     * Post-processing activities
     * When the order returns from the processor, if PDT was successful, this stores the results in order-status-history and logs data for subsequent reference
     *
     * @return boolean
     */
    function after_process($insert_id, $order) {
        global $db;
        if ($_SESSION['paypal_transaction_PDT_passed'] != true) {
            KCLogger::Log('after_process  1');
            $_SESSION['order_created'] = '';
            unset($_SESSION['paypal_transaction_PDT_passed']);
            return false;
        } else {
            KCLogger::Log('after_process  2');
            // PDT found order to be approved, so add a new OSH record for this order's PP details
            unset($_SESSION['paypal_transaction_PDT_passed']);
            $sql_data_array = array(array('fieldName' => 'orders_id', 'value' => $insert_id, 'type' => 'integer'),
                array('fieldName' => 'orders_status_id', 'value' => $this->order_status, 'type' => 'integer'),
                array('fieldName' => 'date_added', 'value' => 'now()', 'type' => 'noquotestring'),
                array('fieldName' => 'customer_notified', 'value' => 0, 'type' => 'integer'),
                array('fieldName' => 'comments', 'value' => 'PayPal status: ' . $this->pdtData['payment_status'] . ' ' . ' @ ' . $this->pdtData['payment_date'] . "\n" . ' Trans ID:' . $this->pdtData['txn_id'] . "\n" . ' Amount: ' . $this->pdtData['mc_gross'] . ' ' . $this->pdtData['mc_currency'] . '.', 'type' => 'string'));
            $db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
            ipn_debug_email('PDT NOTICE :: Order added: ' . $insert_id . "\n" . 'PayPal status: ' . $this->pdtData['payment_status'] . ' ' . ' @ ' . $this->pdtData['payment_date'] . "\n" . ' Trans ID:' . $this->pdtData['txn_id'] . "\n" . ' Amount: ' . $this->pdtData['mc_gross'] . ' ' . $this->pdtData['mc_currency']);
            KCLogger::Log('after_process  3: ' . json_encode($sql_data_array));
            // store the PayPal order meta data -- used for later matching and back-end processing activities
            $sql_data_array = array('order_id' => $insert_id,
                'txn_type' => $this->pdtData['txn_type'],
                'module_name' => $this->code . ' ' . $this->codeVersion,
                'module_mode' => 'PDT',
                'reason_code' => $this->pdtData['reasoncode'],
                'payment_type' => $this->pdtData['payment_type'],
                'payment_status' => $this->pdtData['payment_status'],
                'pending_reason' => $this->pdtData['pendingreason'],
                'invoice' => $this->pdtData['invoice'],
                'first_name' => $this->pdtData['first_name'],
                'last_name' => $this->pdtData['last_name'],
                'payer_business_name' => $order->billing['company'],
                'address_name' => $order->billing['name'],
                'address_street' => $order->billing['street_address'],
                'address_city' => $order->billing['city'],
                'address_state' => $order->billing['state'],
                'address_zip' => $order->billing['postcode'],
                'address_country' => $this->pdtData['residence_country'], // $order->billing['country']
                'address_status' => $this->pdtData['address_status'],
                'payer_email' => $this->pdtData['payer_email'],
                'payer_id' => $this->pdtData['payer_id'],
                'payer_status' => $this->pdtData['payer_status'],
                'payment_date' => datetime_to_sql_format($this->pdtData['payment_date']),
                'business' => $this->pdtData['business'],
                'receiver_email' => $this->pdtData['receiver_email'],
                'receiver_id' => $this->pdtData['receiver_id'],
                'txn_id' => $this->pdtData['txn_id'],
                'parent_txn_id' => $this->pdtData['parent_txn_id'],
                'num_cart_items' => (float) $this->pdtData['num_cart_items'],
                'mc_gross' => (float) $this->pdtData['mc_gross'],
                'mc_fee' => (float) $this->pdtData['mc_fee'],
                'mc_currency' => $this->pdtData['mc_currency'],
                'settle_amount' => (float) $this->pdtData['settle_amount'],
                'settle_currency' => $this->pdtData['settle_currency'],
                'exchange_rate' => ($this->pdtData['exchange_rate'] > 0 ? $this->pdtData['exchange_rate'] : 1.0),
                'notify_version' => (float) $this->pdtData['notify_version'],
                'verify_sign' => $this->pdtData['verify_sign'],
                'date_added' => 'now()',
                'memo' => '{Successful PDT Confirmation - Record auto-generated by payment module}'
            );
//TODO: $db->perform vs zen_db_perform
            zen_db_perform(TABLE_PAYPAL, $sql_data_array);
            KCLogger::Log('after_process  5: ' . json_encode($sql_data_array));
            ipn_debug_email('PDT NOTICE :: paypal table updated: ' . print_r($sql_data_array, true));
        }
    }

    /**
     * Check to see whether module is installed
     *
     * @return boolean
     */
    function check() {
        global $db;
        if (!isset($this->_check)) {
            $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYPAL_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    function _getPDTresults($orderAmount, $my_currency, $pdtTX) {
        global $db;
        $_GET['tx'] = $_POST['tx'];
        KCLogger::Log('PDTresults:  1: ' . $_GET['tx']);
        $ipnData = ipn_postback('PDT', $pdtTX);
        KCLogger::Log('PDTresults:  2: ' . json_encode($ipnData));
        $respdata = $ipnData['info'];
        KCLogger::Log('PDTresults:  3: ' . json_encode($respdata));
        // parse the data
        $lines = explode("\n", $respdata);
        $this->pdtData = array();
        for ($i = 1; $i < count($lines); $i++) {
            if (!strstr($lines[$i], "="))
                continue;
            list($key, $val) = explode("=", $lines[$i]);
            $this->pdtData[urldecode($key)] = urldecode($val);
        }

        KCLogger::Log('PDTresults:  4: ' . json_encode($this->pdtData));
        if ($this->pdtData['txn_id'] == '' || $this->pdtData['payment_status'] == '') {
            KCLogger::Log('PDTresults:  5');
            ipn_debug_email('PDT Returned INVALID Data. Must wait for IPN to process instead. ' . "\n" . print_r($this->pdtData, true));
            return FALSE;
        } else {
            ipn_debug_email('PDT Returned Data ' . print_r($this->pdtData, true));
        }

        $_POST['mc_gross'] = $this->pdtData['mc_gross'];
        $_POST['mc_currency'] = $this->pdtData['mc_currency'];
        $_POST['business'] = $this->pdtData['business'];
        $_POST['receiver_email'] = $this->pdtData['receiver_email'];

        $PDTstatus = (ipn_validate_transaction($respdata, $this->pdtData, 'PDT') && valid_payment($orderAmount, $my_currency, 'PDT') && $this->pdtData['payment_status'] == 'Completed');

        KCLogger::Log('PDTresults: validate1: ' . (ipn_validate_transaction($respdata, $this->pdtData, 'PDT') ? 'true' : 'false'));
        KCLogger::Log('PDTresults: validate2: ' . (valid_payment($orderAmount, $my_currency, 'PDT') ? 'true' : 'false'));
        KCLogger::Log('PDTresults: validate3: ' . ($this->pdtData['payment_status'] == 'Completed' ? 'true' : 'false'));

        if ($this->pdtData['payment_status'] != '' && $this->pdtData['payment_status'] != 'Completed') {
            ipn_debug_email('PDT WARNING :: Order not marked as "Completed".  Check for Pending reasons or wait for IPN to complete.' . "\n" . '[payment_status] => ' . $this->pdtData['payment_status'] . "\n" . '[pending_reason] => ' . $this->pdtData['pending_reason']);
        }

        $sql = "SELECT order_id, paypal_ipn_id, payment_status, txn_type, pending_reason
                FROM " . TABLE_PAYPAL . "
                WHERE txn_id = :transactionID OR parent_txn_id = :transactionID
                ORDER BY order_id DESC  ";

        $sql = $db->bindVars($sql, ':transactionID', $this->pdtData['txn_id'], 'string');
        KCLogger::Log('PDTresults:  $sql: ' . $sql);
        $ipn_id = $db->Execute($sql);
        if ($ipn_id->RecordCount() != 0) {
            ipn_debug_email('PDT WARNING :: Transaction already exists. Perhaps IPN already added it.  PDT processing ended.');
            $pdtTXN_is_unique = false;
        } else {
            $pdtTXN_is_unique = true;
        }

        $PDTstatus = ($pdtTXN_is_unique && $PDTstatus);
        KCLogger::Log('PDTresults:  final: ' . ($PDTstatus ? 'true' : 'false'));
        if ($PDTstatus == TRUE) $this->transaction_id = $this->pdtData['txn_id'];
        KCLogger::Log('PDTresults:  $this->transaction_id: ' . $this->transaction_id);
        return $PDTstatus;
    }

}