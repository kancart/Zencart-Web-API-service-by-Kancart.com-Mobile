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
require_once 'kancartplugin/class/cls_User.php';
require_once 'kancartplugin/class/cls_Order.php';

class KC_CartItem {

    var $cart_item_id = '';
    var $cart_item_key = '';
    var $item_id = '';
    var $item_title = '';
    var $thumbnail_pic_url = '';
    var $currency = '';
    var $item_price = '';
    var $item_original_price = '';
    var $qty = '';
    var $display_skus = '';
    var $item_url = '';
    var $thumb_description = '';
    var $post_free = '';

    function KC_CartItem() {
        
    }

}

class KC_PriceInfo {

    var $name = '';
    var $type = '';
    var $price = '';
    var $home_currency_price;
    var $currency = '';
    var $position = '';

    function KC_PriceInfo() {
        
    }

}

class KC_ShippingMethod {

    var $sm_id;
    var $sm_code;
    var $title;
    var $description;
    var $price;
    var $currency;

    function KC_ShippingMethod() {
        
    }

}

class KC_ReviewOrder {

    var $order_id;
    var $cart_items;
    var $price_infos;
    var $leading_time;
    var $shipping_methods;
    var $selected_shipping_method_id;
    var $cart_item_shipping_methods;
    var $selected_shipping_methods;
    var $coupon_id;
    var $coupons;
    var $selected_coupon_id;

    function KC_ReviewOrder() {
        
    }

}

class KC_Coupon {

    var $coupon_id;
    var $display_id;
    var $expiry_date;
    var $use_conditions;
    var $description;
    var $currency;
    var $price;

    function KC_Coupon() {
        
    }

}

/**
 * 获取购物车商品详细信息
 * @return cart detail
 */
function kancart_shoppingcart_get() {
    Global $db, $currencies, $messages;
    if (!$messages) {
        $messages = array();
    }
    $shoppingcartInfo = array();
    // Validate Cart for checkout
    $_SESSION['valid_to_checkout'] = true;
    $_SESSION['cart_errors'] = '';
    $_SESSION['cart']->get_products(true);

    // 如果购物车中没有商品 那么直接返回空的数组
    if ($_SESSION['cart']->count_contents() <= 0) {
        $shoppingcartInfo['cart_items'] = array();
        $shoppingcartInfo['price_infos'] = array();
        $shoppingcartInfo['messages'] = array();
        $shoppingcartInfo['cart_items_count'] = 0;
        return $shoppingcartInfo;
    }

    if (!$_SESSION['valid_to_checkout']) {
        // 购物车验证失败
        $messages[] = ERROR_CART_UPDATE . $_SESSION['cart_errors'];
        //$messageStack->add('shopping_cart', ERROR_CART_UPDATE . $_SESSION['cart_errors'] , 'caution');
    }

    // 货物重量
    $shipping_weight = $_SESSION['cart']->show_weight();


    // 购物车总价
    $currencies = new currencies(); //classes/currencies.php
    $total = $currencies->value(zen_add_tax($_SESSION['cart']->show_total(), zen_get_tax_rate($products_tax_class_id)), false) * $currencies->currencies[$_SESSION['currency']]['value'];

    // 是否有商品缺货
    $flagAnyOutOfStock = false;

    $cartItems = array();
    $products = $_SESSION['cart']->get_products();
    for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
        $attrArray = '';
        $productsName = $products[$i]['name'];
        // Push all attributes information in an array
        if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
            if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
                $options_order_by = ' ORDER BY LPAD(popt.products_options_sort_order,11,"0")';
            } else {
                $options_order_by = ' ORDER BY popt.products_options_name';
            }

            foreach ($products[$i]['attributes'] as $option => $value) {
                $attributes = "SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                     FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                     WHERE pa.products_id = :productsID
                     AND pa.options_id = :optionsID
                     AND pa.options_id = popt.products_options_id
                     AND pa.options_values_id = :optionsValuesID
                     AND pa.options_values_id = poval.products_options_values_id
                     AND popt.language_id = :languageID
                     AND poval.language_id = :languageID " . $options_order_by;

                $attributes = $db->bindVars($attributes, ':productsID', $products[$i]['id'], 'integer');
                $attributes = $db->bindVars($attributes, ':optionsID', $option, 'integer');
                $attributes = $db->bindVars($attributes, ':optionsValuesID', $value, 'integer');
                $attributes = $db->bindVars($attributes, ':languageID', $_SESSION['languages_id'], 'integer');
                $attributes_values = $db->Execute($attributes);
                //clr 030714 determine if attribute is a text attribute and assign to $attr_value temporarily
                if ($value == PRODUCTS_OPTIONS_VALUES_TEXT_ID) {
                    $attr_value = htmlspecialchars($products[$i]['attributes_values'][$option], ENT_COMPAT, CHARSET, TRUE);
                } else {
                    $attr_value = $attributes_values->fields['products_options_values_name'];
                }
                $attrArray .= $attributes_values->fields['products_options_name'] . ":" . $attr_value . "; ";
            }
        } //end foreach [attributes]
        if (STOCK_CHECK == 'true') {
            $flagStockCheck = zen_get_products_stock($products[$i]['id']) - $products[$i]['quantity'];
            if ($flagStockCheck < 0) {
                $flagAnyOutOfStock = true;
            }
        }
        $productsImage = $products[$i]['image'];

        $cartItem = new KC_CartItem();
        $cartItem->cart_item_id = $products[$i]['id'];
        $cartItem->cart_item_key = '';
        $cartItem->item_id = $products[$i]['id'];
        if (isEmptyString($productsImage)) {
            $productsImage = 'no_picture.gif';
        }
        $cartItem->thumbnail_pic_url = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $productsImage;

        if (is_int(strpos($productsImage, ','))) {
            $imgsrcs = split(',', $productsImage);
            $cartItem->thumbnail_pic_url = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $imgsrcs[0];
        }
        $cartItem->item_title = $productsName;
        $cartItem->item_price = $currencies->value(zen_add_tax($products[$i]['final_price'], zen_get_tax_rate($products[$i]['tax_class_id'])), false) * $currencies->currencies[$_SESSION['currency']]['value'];
        $cartItem->qty = $products[$i]['quantity'];
        $cartItem->display_skus = $attrArray;

        $cartItems[] = $cartItem;

        //$cartItem->cart_item_id =
    } // end FOR loop
    $priceInfos = array();
    $priceInfo = new KC_PriceInfo();
    $priceInfo->name = "Grand Total";
    $priceInfo->type = "total";
    $priceInfo->price = $total;
    $priceInfo->currency = $_SESSION['currency'];
    $priceInfo->position = 0;
    $priceInfos[] = $priceInfo;

    $shoppingcartInfo['is_virtual'] = $_SESSION['cart']->get_content_type() == 'virtual' ? true : false;
    $shoppingcartInfo['cart_items'] = $cartItems;
    $shoppingcartInfo['price_infos'] = $priceInfos;
    $shoppingcartInfo['cart_items_count'] = $_SESSION['cart']->count_contents();
    $shoppingcartInfo['messages'] = $messages;
    // 是否允许使用 PayPal 快速支付
    $shoppingcartInfo['payment_methods'] = array();
    if ($total > 0 && defined('MODULE_PAYMENT_PAYPALWPP_STATUS')) {
        $shoppingcartInfo['payment_methods'][] = 'paypalec';
    }

    return $shoppingcartInfo;
}

/**
 * 添加商品至购物车
 * @return cart detail
 */
function kancart_shoppingcart_add() {
    Global $messages;
    if (!$messages) {
        $messages = array();
    }
    unset($_SESSION['kancart_paypal_token']);
    unset($_SESSION['kancart_paypal_payerID']);
    unset($_SESSION['shipping']);
    unset($_SESSION['cc_id']);
    // 从session 中获取购物车商品信息
    $products = $_SESSION['cart']->get_products();

    $item_id = $_POST['item_id'];
    $skus = json_decode($_POST['skus'], true);
    KCLogger::Log('ShoppingCartAdd: $item_id:' . $item_id);
    KCLogger::Log('ShoppingCartAdd: $skus:' . $_POST['skus']);
    if (isset($item_id) && is_numeric($item_id)) {
        // 先验证属性和数量
        $the_list = '';
        $adjust_max = 'false';
        if (isset($skus)) {
            foreach ($skus as $key => $value) {
                $check = zen_get_attributes_valid($item_id, $value['sku_id'], $value['value']);
                if ($check == false) {
                    KCLogger::Log('ShoppingCartAdd: $check false: ' . $value['sku_id'] . ' ' . $value['value']);
                    $the_list = TEXT_ERROR_OPTION_FOR . zen_options_name($value['sku_id']) . TEXT_INVALID_SELECTION . (zen_values_name($value['value']) == 'TEXT' ? TEXT_INVALID_USER_INPUT : zen_values_name($value['value']));
                }
            }
        }
        KCLogger::Log('ShoppingCartAdd: $the_list: ' . $the_list);
        // 验证添加的数量
        //          $real_ids = $_POST['id'];
        //die('I see Add to Cart: ' . $_POST['products_id'] . 'real id ' . zen_get_uprid($_POST['products_id'], $real_ids) . ' add qty: ' . $add_max . ' - cart qty: ' . $cart_qty . ' - newqty: ' . $new_qty);
        $add_max = zen_get_products_quantity_order_max($item_id);
        $cart_qty = $_SESSION['cart']->in_cart_mixed($item_id);
        $new_qty = isset($_POST['qty']) ? $_POST['qty'] : 1;

        //echo 'I SEE actionAddProduct: ' . $_POST['products_id'] . '<br>';
        $new_qty = $_SESSION['cart']->adjust_quantity($new_qty, $item_id, 'shopping_cart');

        if (($add_max == 1 and $cart_qty == 1)) {
            KCLogger::Log('ShoppingCartAdd: add_max = 1 do not add');
            // do not add
            $new_qty = 0;
            $adjust_max = 'true';
        } else {
            // adjust quantity if needed
            if (($new_qty + $cart_qty > $add_max) and $add_max != 0) {
                $adjust_max = 'true';
                $new_qty = $add_max - $cart_qty;
            }
        }
        KCLogger::Log('ShoppingCartAdd: $adjust_max: ' . $adjust_max);
        KCLogger::Log('ShoppingCartAdd: $add_max: ' . $add_max);
        if ((zen_get_products_quantity_order_max($item_id) == 1 and $_SESSION['cart']->in_cart_mixed($item_id) == 1)) {
            // do not add
            KCLogger::Log('ShoppingCartAdd: do not add 2');
        } else {
            // process normally
            // bof: set error message
            if ($the_list != '') {
                $messages[] = ERROR_CORRECTIONS_HEADING . $the_list;
                KCLogger::Log('ShoppingCartAdd: error: ' . $the_list);
                // $messageStack->add('product_info', ERROR_CORRECTIONS_HEADING . $the_list, 'caution');
                // 属性验证失败
                // 返回错误
                //          $messageStack->add('header', 'REMOVE ME IN SHOPPING CART CLASS BEFORE RELEASE<br/><BR />' . ERROR_CORRECTIONS_HEADING . $the_list, 'error');
            } else {
                // txt_10	11111111111
                // 多选是  sku_id  下面放 value_id的数组
                // 单选直接是 sku_id 对应 value_id
                $sku_array = array();
                foreach ($skus as $key => $value) {
                    $one_sku = $value;
                    if (isset($value['mode']) && $value['mode'] == 'input') {
                        $sku_array[TEXT_PREFIX . $value['sku_id']] = $value['value'];
                    } else if (isset($value['mode']) && $value['mode'] == 'multiple_select') {
                        $sku_array[$value['sku_id']] = split(',', $value['value']);
                    } else if (isset($value['mode']) && $value['mode'] == 'select') {
                        $sku_array[$value['sku_id']] = $value['value'];
                    }
                }
                $_SESSION['cart']->add_cart($item_id, $_SESSION['cart']->get_quantity(zen_get_uprid($item_id, $sku_array)) + ($new_qty), $sku_array);
                $finalQty = $_SESSION['cart']->get_quantity(zen_get_uprid($item_id, $sku_array)) + ($new_qty);
                KCLogger::Log('ShoppingCartAdd: add cart: ' . $item_id . ' qty: ' . $finalQty . ' skus: ' . json_encode($sku_array));
                // iii 030813 end of changes.
            } // eof: set error message
        } // eof: quantity maximum = 1
        // 自动调整最大数
        if ($adjust_max == 'true') {
            $messages[] = ERROR_MAXIMUM_QTY . zen_get_products_name($item_id);
            // 增加错误提示，告诉用户商品的最大购买数量
        }
    }
    return kancart_shoppingcart_get();
}

/**
 * 更新购物车商品的数量
 * @return cart detail
 */
function kancart_shoppingcart_update() {
    global $messages;
    if (!$messages) {
        $messages = array();
    }
    unset($_SESSION['kancart_paypal_token']);
    unset($_SESSION['kancart_paypal_payerID']);
    unset($_SESSION['shipping']);
    unset($_SESSION['cc_id']);
    $item_id = $_POST['cart_item_id'];
    $quantity = $_POST['qty'];
    //	$skus = json_decode($_POST['skus']);


    $adjust_max = 'false';
    if ($quantity == '') {
        $quantity = 0;
    }
    if (!is_numeric($quantity) || $quantity <= 0) {
        // 数量小于等于0 移除商品
        $messages[] = ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . zen_get_products_name($item_id) . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($item_id);
        $_SESSION['cart']->remove($item_id);
    }

    $add_max = zen_get_products_quantity_order_max($item_id);
    $cart_qty = $_SESSION['cart']->in_cart_mixed($item_id);
    $new_qty = $quantity;

    //echo 'I SEE actionUpdateProduct: ' . $_POST['products_id'] . ' ' . $_POST['products_id'][$i] . '<br>';
    $new_qty = $_SESSION['cart']->adjust_quantity($new_qty, $item_id, 'shopping_cart');

    //die('I see Update Cart: ' . $_POST['products_id'][$i] . ' add qty: ' . $add_max . ' - cart qty: ' . $cart_qty . ' - newqty: ' . $new_qty);
    if (($add_max == 1 and $cart_qty == 1)) {
        // do not add
        $adjust_max = 'true';
    } else {
        // adjust quantity if needed
        if (($new_qty + $cart_qty > $add_max) and $add_max != 0) {
            $adjust_max = 'true';
            $new_qty = $add_max;
        }
        // TODO 获取属性
        //		$sku_array = array();
        //		foreach ($skus as $key => $value) {
        //			$one_sku = $value;
        //			if (isset($value['mode']) && $value['mode'] == 'input') {
        //				$sku_array[TEXT_PREFIX.$value['sku_id']] = $value['value'];
        //			} else if (isset($value['mode']) && $value['mode'] == 'multi_select') {
        //				$sku_array[$value['sku_id']] = split(',', $value['value']);
        //			} else if (isset($value['mode']) && $value['mode'] == 'select') {
        //				$sku_array[$value['sku_id']] = $value['value'];
        //			}
        //		}
        //$_SESSION['cart']->update_quantity($item_id, $new_qty);
        Global $db;
        $_SESSION['cart']->contents[$item_id]['qty'] = (float) $new_qty;
        // update database
        if (isset($_SESSION['customer_id'])) {
            $sql = "update " . TABLE_CUSTOMERS_BASKET . "
                set customers_basket_quantity = '" . (float) $new_qty . "'
                where customers_id = '" . (int) $_SESSION['customer_id'] . "'
                and products_id = '" . zen_db_input($item_id) . "'";
            $db->Execute($sql);
        }
    }
    if ($adjust_max == 'true') {
        $messages[] = ERROR_MAXIMUM_QTY . zen_get_products_name($item_id);
    }
    return kancart_shoppingcart_get();
}

/**
 * 从购物车中移除商品
 * @return cart detail
 */
function kancart_shoppingcart_remove() {
    unset($_SESSION['kancart_paypal_token']);
    unset($_SESSION['kancart_paypal_payerID']);
    unset($_SESSION['shipping']);
    unset($_SESSION['cc_id']);
    $_SESSION['cart']->remove($_POST['cart_item_id']);
    return kancart_shoppingcart_get();
}

function kancart_shoppingcart_checkout() {
    Global $order, $order_total_modules;
    require_once DIR_WS_CLASSES . 'order.php';
    $order = new order;
    require_once DIR_WS_CLASSES . 'shipping.php';
    $shipping_modules = new shipping($_SESSION['shipping']);
    require_once DIR_WS_CLASSES . 'order_total.php';
    $order_total_modules = new order_total;
    $order_totals = $order_total_modules->pre_confirmation_check();
    $order_totals = $order_total_modules->process();

    $priceInfos = kancart_shoppingcart_checkout_priceinfos_get();
    $total = 0;
    foreach ($priceInfos as $priceInfo) {
        if ($priceInfo->type == 'total') {
            $total = $priceInfo->price;
            break;
        }
    }
    if ($total == 0) {
        $_POST['payment_method_id'] = 'freecharger';
    }

    if ($_POST['payment_method_id'] == 'freecharger') {
        $returnResults = kancart_shoppingcart_freecharger_pay();
    } else if ($_POST['payment_method_id'] == 'paypalwpp') {
        $_GET['markflow'] = 1;
        $_GET['clearSess'] = 1;
        $_GET['stage'] = 'final';
        $returnResults = kancart_shoppingcart_paypalec_start();
    } else if ($_POST['payment_method_id'] == 'paypal') {
        $returnResults = kancart_shoppingcart_paypalwps_start();
    }
    return $returnResults;
}

function kancart_shoppingcart_freecharger_pay() {
    Global $kcResponse, $order, $order_totals, $db, $order_total_modules, $template, $template_dir, $current_page_base, $language_page_directory, $currencies;
    KCLogger::Log('FreeCharger.Pay: 1-1: ' . json_encode($_SESSION['shipping']));
    $language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';
    $current_page_base = 'checkout_process';
    KCLogger::Log('FreeCharger.Pay: 2-1');
    require_once DIR_WS_MODULES . zen_get_module_directory('require_languages.php');
    KCLogger::Log('FreeCharger.Pay: 2-2');
    require_once DIR_WS_CLASSES . 'order.php';
    $order = new order;
    KCLogger::Log('FreeCharger.Pay: 3: ' . json_encode($_SESSION['shipping']));
    require_once DIR_WS_CLASSES . 'shipping.php';
    $shipping_modules = new shipping($_SESSION['shipping']);
    KCLogger::Log('FreeCharger.Pay: 4');
    require_once DIR_WS_CLASSES . 'order_total.php';
    $order_total_modules = new order_total;
    $order_totals = $order_total_modules->pre_confirmation_check();
    $order_totals = $order_total_modules->process();
    KCLogger::Log('FreeCharger.Pay: 5');
    // add kancart order comment
    $order->info['comments'] .= '\n' . isset($_REQUEST['custom_kc_comments']) ? $_REQUEST['custom_kc_comments'] : '';
    $insert_id = $order->create($order_totals, 2);
    $order->create_add_products($insert_id);
    $order->send_order_email($insert_id, 2);
    KCLogger::Log('FreeCharger.Pay: 6');

    $_SESSION['cart']->reset(true);
    KCLogger::Log('FreeCharger.Pay: 7');
// unregister session variables used during checkout
    unset($_SESSION['sendto']);
    unset($_SESSION['billto']);
    unset($_SESSION['shipping']);
    unset($_SESSION['payment']);
    unset($_SESSION['comments']);
    $order_total_modules->clear_posts(); //ICW ADDED FOR CREDIT CLASS SYSTEM
    KCLogger::Log('FreeCharger.Pay: 8');

//            $display_amt = $currency_cd . ' ' . $amt;
    $returnResults = array();
    $returnResults['payment_total'] = $order->info['total'];
    $returnResults['currency'] = $order->info['currency'];
    $returnResults['transaction_id'] = $insert_id;
    $returnResults['order_id'] = $insert_id;
    if (kancart_order_isExist($insert_id)) {
        $kcorder = new KC_Order($insert_id);
        if ($kcorder instanceof KC_Order) {
            $returnResults['orders'][] = $kcorder;
        }
    }
    KCLogger::Log('FreeCharger.Pay: 9 ' . json_encode($returnResults));
    return $returnResults;
}

/**
 * 开始一个 PayPal ExpressCheckout 支付请求
 */
function kancart_shoppingcart_paypalec_start($payAction = 'commit') {
    Global $kcResponse, $order, $order_totals, $db, $paypalwpp;
    require_once 'kancartplugin/PayPal/PayPalWPP.php';
    $paypalwpp = new paypalwpp();
    $results = $paypalwpp->doSetExpressCheckout();
    return $results;
}

/**
 * 获取当前 PayPal ExpressCheckout 支付的详情
 */
function kancart_shoppingcart_paypalec_detail($returnDetail = true) {
    require_once 'kancartplugin/PayPal/PayPalWPP.php';
    $paypalwpp = new PayPalWPP();
    $paypalwpp->doGetExpressCheckoutDetail();
    if ($returnDetail == true) {
        $returnResults = checkoutFlow();
        if (!hasLogin() && isset($_SESSION['customer_id'])) {
            $user = kancart_user_get();
            $_SESSION['kancart_session_key'] = md5($user->uname . time());
            $_SESSION['kancart_last_login_date'] = time();
            $_SESSION['kancart_login_uname'] = $user->uname;
            $returnResults['sessionkey'] = $_SESSION['kancart_session_key'];
            $returnResults['uname'] = $user->uname;
        }
        return $returnResults;
    }
}

function kancart_shoppingcart_paypalec_pay() {
    Global $kcResponse, $order, $order_totals, $db, $paypalwpp, $order_total_modules, $template, $template_dir, $current_page_base, $language_page_directory, $currencies, $insert_id;
    KCLogger::Log('PayPalEC.Pay: 1-1: ' . json_encode($_SESSION['shipping']));
    if (!isset($_SESSION['paypal_ec_payer_id']) || $_SESSION['paypal_ec_payer_id'] == '') {
        kancart_shoppingcart_paypalec_detail(false);
    }
    KCLogger::Log('PayPalEC.Pay: 1-2: ' . json_encode($_SESSION['shipping']));
    KCLogger::Log('PayPalEC.Pay: 1  paypal_ec_payer_id: ' . $_SESSION['paypal_ec_payer_id']);
    $language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';
    $current_page_base = 'checkout_process';
    KCLogger::Log('PayPalEC.Pay: 2-1: ' . json_encode($_SESSION['shipping']));
    require_once DIR_WS_MODULES . zen_get_module_directory('require_languages.php');
    KCLogger::Log('PayPalEC.Pay: 2-2: ' . json_encode($_SESSION['shipping']));
    require_once DIR_WS_CLASSES . 'order.php';
    $order = new order;
    KCLogger::Log('PayPalEC.Pay: 3: ' . json_encode($_SESSION['shipping']));
    require_once DIR_WS_CLASSES . 'shipping.php';
    $shipping_modules = new shipping($_SESSION['shipping']);
    KCLogger::Log('PayPalEC.Pay: 4');
    require_once DIR_WS_CLASSES . 'order_total.php';
    $order_total_modules = new order_total;
    $order_totals = $order_total_modules->pre_confirmation_check();
    $order_totals = $order_total_modules->process();
    KCLogger::Log('PayPalEC.Pay: 5');
    require_once 'kancartplugin/PayPal/PayPalWPP.php';
    $paypalwpp = new PayPalWPP();
    $response = $paypalwpp->before_process();
    $insert_id = $order->create($order_totals, 2);
    $order->create_add_products($insert_id);
    $order->send_order_email($insert_id, 2);
    KCLogger::Log('PayPalEC.Pay: 6');

// load the after_process function from the payment modules
    $paypalwpp->after_process();
    KCLogger::Log('PayPalEC.Pay: 7');
    $_SESSION['cart']->reset(true);
    KCLogger::Log('PayPalEC.Pay: 8');
// unregister session variables used during checkout
    unset($_SESSION['sendto']);
    unset($_SESSION['billto']);
    unset($_SESSION['shipping']);
    unset($_SESSION['payment']);
    unset($_SESSION['comments']);
    $order_total_modules->clear_posts(); //ICW ADDED FOR CREDIT CLASS SYSTEM
    KCLogger::Log('PayPalEC.Pay: 9');

    foreach ($response as $key => $value) {
        $response[$key] = urldecode($value);
    }

    $tran_ID = $response['PAYMENTINFO_0_TRANSACTIONID'];

    $amt = $response['PAYMENTINFO_0_AMT'];
    $currency_cd = $response['PAYMENTINFO_0_CURRENCYCODE'];
//            $display_amt = $currency_cd . ' ' . $amt;
    $returnResults = array();
    $returnResults['payment_total'] = $amt;
    $returnResults['currency'] = $currency_cd;
    $returnResults['transaction_id'] = $tran_ID;
    $returnResults['orders'] = array();
    if (kancart_order_isExist($insert_id)) {
        $kcorder = new KC_Order($insert_id);
        if ($kcorder instanceof KC_Order) {
            $returnResults['orders'][] = $kcorder;
        }
    }
    KCLogger::Log('PayPalEC.Pay: 10 ' . json_encode($returnResults));
    return $returnResults;
}

function kancart_shoppingcart_paypalwps_start() {
    Global $order, $shipping_modules, $order_total_modules, $order_totals, $currencies;
    KCLogger::Log('PayPalWPS: 0.1');
    $lang_file = zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/', 'paypal.php', 'false');
    if (@file_exists($lang_file)) {
        include_once($lang_file);
    }
    KCLogger::Log('PayPalWPS: 0.2');
    require_once 'kancartplugin/PayPal/PayPalWPS.php';
    KCLogger::Log('PayPalWPS: 0.3');
    // Step 1: 确认购物车内是否有商品
    // if there is nothing in the customers cart, redirect them to the shopping cart page
    if ($_SESSION['cart']->count_contents() <= 0) {
        zen_redirect(zen_href_link(FILENAME_TIME_OUT));
    }
    KCLogger::Log('PayPalWPS: 1');
    // Step 2: 确认是否登录
    // Step 3: 确认是否选中运输方式
    // if no shipping method has been selected, redirect the customer to the shipping method selection page
    if (!$_SESSION['shipping']) {
        
    }
    if (isset($_SESSION['shipping']['id']) && $_SESSION['shipping']['id'] == 'free_free' && defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER') && $_SESSION['cart']->show_total() < MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) {
        
    }
    KCLogger::Log('PayPalWPS: 2');
    // Step 4: 保存留言
    $_SESSION['comments'] = zen_db_prepare_input($_POST['comments']);
    KCLogger::Log('PayPalWPS: 3');

    $_SESSION['payment'] = 'paypal';

    require_once (DIR_WS_CLASSES . 'order.php');
    $order = new order;
    KCLogger::Log('PayPalWPS: 3.1');
// load the selected shipping module
    require_once (DIR_WS_CLASSES . 'shipping.php');
    $shipping_modules = new shipping($_SESSION['shipping']);
    KCLogger::Log('PayPalWPS: 3.2');
    require_once (DIR_WS_CLASSES . 'order_total.php');
    $order_total_modules = new order_total;
    KCLogger::Log('PayPalWPS: 3.3');
    $order_total_modules->collect_posts();
    KCLogger::Log('PayPalWPS: 3.4');
    $order_total_modules->pre_confirmation_check();
    KCLogger::Log('PayPalWPS: 3.5');
    $order_totals = $order_total_modules->process();
    KCLogger::Log('PayPalWPS: 4');

    require_once DIR_WS_CLASSES . 'payment.php';
    $payment_modules = new payment('paypal');
    KCLogger::Log('PayPalWPS: 5');
    //@debug echo ($credit_covers == true) ? 'TRUE' : 'FALSE';
    $paypalWPS = new PayPalWPS();
    $paypalWPS->update_status();
    KCLogger::Log('PayPalWPS: 6');
    $returnResult = array();
    $returnResult['paypal_redirect_url'] = $paypalWPS->form_action_url;
    $returnResult['paypal_params'] = $paypalWPS->getParams();
    $returnResult['paypal_params']['shopping_url'] = $_POST['shoppingcart_url'];
    $returnResult['paypal_params']['return'] = $_POST['return_url'];
    $returnResult['paypal_params']['cancel_return'] = $_POST['cancel_url'];

    return $returnResult;
}

function kancart_shoppingcart_paypalwps_done() {
    Global $order, $order_total_modules, $db;

    KCLogger::Log('PayPalWPS Done:  1');
    require_once 'kancartplugin/PayPal/PayPalWPS.php';
    KCLogger::Log('PayPalWPS Done:  2');
    $returnResults = array();
    require_once DIR_WS_CLASSES . 'payment.php';
    $payment_modules = new payment('paypal');
    KCLogger::Log('PayPalWPS Done:  3');
    // paypal wps
    $paypalWPS = new PayPalWPS();
    KCLogger::Log('PayPalWPS Done:  4');
    $returnResults = $paypalWPS->checkoutProcess();
    KCLogger::Log('PayPalWPS Done:  5');
    $returnResults['orders'] = array();
    if (kancart_order_isExist($returnResults['order_id'])) {
        $kcorder = new KC_Order($returnResults['order_id']);
        if ($kcorder instanceof KC_Order) {
            $returnResults['orders'][] = $kcorder;
        }
    }
    KCLogger::Log('PayPalWPS Done:  6: ' . json_encode($returnResults));
    return $returnResults;
}

/**
 * 结算时更新用户地址
 * @return cart checkout detail
 */
function kancart_shoppingcart_address_update() {
    return checkoutFlow();
}

/**
 * 更新用户选择的运输方式
 * @return cart checkout detail
 */
function kancart_shoppingcart_shippingmethods_update() {
    return checkoutFlow();
}

/**
 * 更新订单所使用的coupon
 * @return checkout detail
 */
function kancart_shoppingcart_coupons_update() {
    return checkoutFlow();
}

/**
 * 获取结算详细信息
 * @return checkout detail
 */
function kancart_shoppingcart_checkout_detail() {
    return checkoutFlow();
}

/**
 * 获取可用的运输方式
 * @return shipping methods
 */
function kancart_shoppingcart_shippingmethods_get($free_shipping = false) {
    Global $quotes, $currencies;
    $shippingMethodArray = array();
    foreach ($quotes as $key => $value) {
        $size = sizeof($value['methods']);
        for ($i = 0; $i < $size; $i++) {
            $sm = new KC_ShippingMethod();
            $sm_array = $value['methods'][$i];
            if ($sm_array && (!isset($value['error']) || isEmptyString($value['error']))) {
                $sm->sm_id = $value['id'] . '_' . $sm_array['id'];
                $sm->sm_code = $sm_array['id'];
                $sm->title = (($free_shipping == true) ? $sm_array['title'] : $value['module'] . ' (' . $sm_array['title'] . ')');
                $sm->price = $currencies->value(zen_add_tax($sm_array['cost'], (isset($sm_array['tax']) ? $sm_array['tax'] : 0)), false) * $currencies->currencies[$_SESSION['currency']]['value'];
                $sm->currency = $_SESSION['currency'];
                $sm->description = array_key_exists('description', $value) ? $value['description'] : '';
                if ($sm_array['title'] != MODULE_SHIPPING_ZONES_UNDEFINED_RATE) {
                    $shippingMethodArray[] = $sm;
                }
            }
        }
    }
    return $shippingMethodArray;
}

/**
 * 获取价格信息
 * @return price infos
 */
function kancart_shoppingcart_checkout_priceinfos_get() {
    Global $order_total_modules, $currencies;
    require_once DIR_WS_CLASSES . 'order_total.php';
    $order_total_modules = new order_total;
    $order_total_modules->collect_posts();
    $order_total_modules->pre_confirmation_check();
    $priceInfoArrays = array();
    if (MODULE_ORDER_TOTAL_INSTALLED) {
        $order_totals = $order_total_modules->process();
        $size = sizeof($order_totals);
        for ($i = 0; $i < $size; $i++) {
            $priceInfo = new KC_PriceInfo();
            $priceInfo->currency = $_SESSION['currency'];
            $priceInfo->name = $order_totals[$i]['title'];
            $priceInfo->price = $currencies->value($order_totals[$i]['value'], false) * $currencies->currencies[$_SESSION['currency']]['value'];
            $priceInfo->type = $order_totals[$i]['code'] == 'ot_total' ? 'total' : $order_totals[$i]['code'];
            if ($priceInfo->type == 'ot_shipping') {
                $priceInfo->type = 'shipping';
                $priceInfo->name = 'Shipping Cost:';
            }
            $priceInfo->position = $order_totals[$i][sort_order];
            $priceInfoArrays[] = $priceInfo;
        }
    }
    return $priceInfoArrays;
}

/**
 * 验证session中 customer_id 对应的用户是否真实有效
 * @return boolean
 */
function kancart_validate_user() {
    // 判断session中 customer_id是否存在，如果存在则继续判断该用户是否真实有效
    // 判断session_key 是否有效
    if (isset($_SESSION['customer_id']) && zen_get_customer_validate_session($_SESSION['customer_id'])) {
        return true;
    } else {
        return false;
    }
}

/**
 * 验证 address_book_id 是否真实有效
 * @param string $address_book_id
 * @return boolean
 */
function kancart_validate_address_id($address_book_id) {
    Global $db;
    // 验证地址是否存在
    $check_address_query = "SELECT count(*) AS total
                            FROM   " . TABLE_ADDRESS_BOOK . "
                            WHERE  customers_id = :customersID
                            AND    address_book_id = :addressBookID";

    $check_address_query = $db->bindVars($check_address_query, ':customersID', $_SESSION['customer_id'], 'integer');
    $check_address_query = $db->bindVars($check_address_query, ':addressBookID', $address_book_id, 'integer');
    $check_address = $db->Execute($check_address_query);

    if ($check_address->fields['total'] != '1') {
        return false;
    }
    return true;
}

/**
 * 清除运输方式的信息
 */
function kancart_clean_checkout_info() {
    $_SESSION['shipping'] = 'free_free';
    $_SESSION['shipping']['title'] = 'free_free';
    $_SESSION['sendto'] = false;
    $_SESSION['billto'] = false;
}

function checkoutFlow() {
    Global $order, $total_weight, $total_count, $currencies, $order_total_modules, $order_totals, $shipping_weight, $shipping_num_boxes, $quotes, $kcResponse;
    KCLogger::Log('Checkout Flow: 1');
    $currencies = new currencies();
    if ($_SESSION['cart']->count_contents() <= 0) {
        // 购物车没有商品
        $kcResponse->DataBack(array('redirect_to_page' => 'shopping_cart', 'messages' => array()));
    }
    if (zen_get_customer_validate_session($_SESSION['customer_id']) == false) {
        // 用户未登录
    }
    require_once DIR_WS_CLASSES . 'order.php';
    $order = new order;

    $isVirtual = false;
    if ($order->content_type == 'virtual') {
        $_SESSION['shipping'] = false;
        $_SESSION['sendto'] = false;
        $isVirtual = true;
        // 虚拟商品 不需要送货， 直接支付
        // 需要billing 地址
    }
    KCLogger::Log('Checkout Flow: 2');
    if ($_POST['method'] == 'KanCart.ShoppingCart.Addresses.Update') {
        KCLogger::Log('address update: 1');
        if ((isset($_POST['billing_address']) && !isEmptyString($_POST['billing_address']))
                || (isset($_POST['billing_address_book_id']) && !isEmptyString($_POST['billing_address_book_id']))) {
            // 修改 billing address
            // param bill_address_book_id
            // param bill_address  [json]
            $bill_address_id = false;
            if (isset($_POST["billing_address_book_id"])) {
                $bill_address_id = $_POST["billing_address_book_id"];
                // 验证账单地址是否存在
                if (!kancart_validate_address_id($bill_address_id)) {
                    $bill_address_id = '';
                    $_SESSION['billto'] = '';
                } else {
                    $_SESSION['billto'] = $bill_address_id;
                }
            }

            $bill_address = false;
            if (isset($_POST["billing_address"])) {
                $bill_address_json = json_decode($_POST["billing_address"], true);
                if ($bill_address_json) {
                    $bill_address = $bill_address_json;
                } else {
                    // 客户端的 JSON解析错误， 报错
                    $bill_address = false;
                }
            }
            if ($bill_address_id && $bill_address) {
                $bill_address['address_book_id'] = $bill_address_id;
                kancart_user_address_update($bill_address);
            } else if ($bill_address_id) {
                $_SESSION['billto'] = $bill_address_id;
            } else if ($bill_address) {
                $new_address_book_id = kancart_user_address_add($bill_address);
                if ($new_address_book_id) {
                    $_SESSION['billto'] = $new_address_book_id;
                } else {
                    // TODO 添加新的账单地址失败
                }
            }
        }
        if (!$isVirtual &&
                ((isset($_POST['shipping_address']) && !isEmptyString($_POST['shipping_address']))
                || (isset($_POST['shipping_address_book_id']) && !isEmptyString($_POST['shipping_address_book_id'])))) {
            KCLogger::Log('shipping address update: 1');
            // 修改 shipping address
            // param ship_address_book_id
            // param ship_address  [json]
            $ship_address_id = false;
            if (isset($_POST["shipping_address_book_id"]) && !isEmptyString($_POST["shipping_address_book_id"])) {

                $ship_address_id = $_POST["shipping_address_book_id"];
                KCLogger::Log('shipping address update: 2');
                // 验证送货地址是否存在
                if (!kancart_validate_address_id($ship_address_id)) {
                    $ship_address_id = '';
                    kancart_clean_checkout_info();
                    KCLogger::Log('shipping address update: 3');
                } else {
                    $_SESSION['sendto'] = $ship_address_id;
                    KCLogger::Log('shipping address update: 4');
                }
            }
            KCLogger::Log('shipping address update: 5');

            $ship_address = false;
            if (isset($_POST["shipping_address"])) {
                $ship_address_json = json_decode($_POST["shipping_address"], true);
                KCLogger::Log('shipping address update: 6');
                if ($ship_address_json) {
                    $ship_address = $ship_address_json;
                    KCLogger::Log('shipping address update: 7');
                } else {
                    // TODO 客户端的 JSON解析错误， 报错
                    $ship_address = false;
                    KCLogger::Log('shipping address update: 8');
                }
            }

            if ($ship_address_id && $ship_address) {

                $ship_address['address_book_id'] = $ship_address_id;
                kancart_user_address_update($ship_address);
                KCLogger::Log('shipping address update: 9');
            } else if ($ship_address_id) {
                $_SESSION['sendto'] = $ship_address_id;
                KCLogger::Log('shipping address update: 10');
            } else if ($ship_address) {
                $new_address_book_id = kancart_user_address_add($ship_address);
                KCLogger::Log('shipping address update: 11');
                if ($new_address_book_id) {
                    $_SESSION['sendto'] = $new_address_book_id;
                    KCLogger::Log('shipping address update: 12');
                } else {
                    KCLogger::Log('shipping address update: 13');
                    // TODO 添加新的送货地址失败
                }
            }
        }
        $_SESSION['shipping'] = '';
    }

    KCLogger::Log('Checkout Flow: 3');
    require_once DIR_WS_CLASSES . 'http_client.php';
    KCLogger::Log('Checkout Flow: 4');
    // 验证购物车准备结算
    $_SESSION['valid_to_checkout'] = true;
    $_SESSION['cart']->get_products(true);
    if ($_SESSION['valid_to_checkout'] == false) {
        // 购物车验证失败， 需要重新整理
    }
    // 库存检查
    if ((STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true')) {
        $products = $_SESSION['cart']->get_products();
        for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
            if (zen_get_products_stock($products[$i]['id']) - $products[$i]['quantity'] < 0) {
                // TODO 库存检查失败 返回购物车
                break;
            }
        }
    }
    // 验证选中的地址
    if (!$isVirtual && !kancart_validate_address_id($_SESSION['sendto'])) {
        // 验证失败， 设为默认地址
        $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
        $_SESSION['shipping'] = '';
    }

    // 验证选中的地址
    if (!kancart_validate_address_id($_SESSION['billto'])) {
        // 验证失败， 设为默认地址
        $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
    }
    KCLogger::Log('Checkout Flow: 5');
    $order = new order;
    KCLogger::Log('Checkout Flow: 6');
    // 更新一个新的随机的 cartID 防止session 注入攻击
    if (isset($_SESSION['cart']->cartID)) {
        if (!isset($_SESSION['cartID']) || $_SESSION['cart']->cartID != $_SESSION['cartID']) {
            $_SESSION['cartID'] = $_SESSION['cart']->cartID;
        }
    } else {
        // 超时错误
    }

    // if the order contains only virtual products, forward the customer to the billing page as
    // a shipping address is not needed
    // 如果订单只含有虚拟商品， 跳转到支付界面
    if ($isVirtual) {
        $_SESSION['shipping'] = 'free_free';
        $_SESSION['shipping']['title'] = 'free_free';
        $_SESSION['sendto'] = false;
        // 跳转到支付界面
    }

    $total_weight = $_SESSION['cart']->show_weight();
    $total_count = $_SESSION['cart']->count_contents();
    KCLogger::Log('Checkout Flow: 7');
    // load all enabled shipping modules
    require_once DIR_WS_CLASSES . 'shipping.php';
    KCLogger::Log('Checkout Flow: 7-1');
    $shipping_modules = new shipping;
    KCLogger::Log('Checkout Flow: 8');
    // 载入所有可用的送货模块
    $pass = true;
    if (defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true')) {
        $pass = false;
        switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
            case 'national':
                if ($order->delivery['country_id'] == STORE_COUNTRY) {
                    $pass = true;
                }
                break;
            case 'international':
                if ($order->delivery['country_id'] != STORE_COUNTRY) {
                    $pass = true;
                }
                break;
            case 'both':
                $pass = true;
                break;
        }
        $free_shipping = false;
        if (($pass == true) && ($_SESSION['cart']->show_total() >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) {
            $free_shipping = true;
        }
    } else {
        $free_shipping = false;
    }

    // 更新送货方式
    if ($_POST['method'] == 'KanCart.ShoppingCart.ShippingMethods.Update') {
        if (isset($_POST['shipping_method_id'])) {
            $_POST['shipping'] = $_POST['shipping_method_id'];
        } else {
            $_POST['shipping'] = '';
        }

        $quote = array();
        if ((zen_count_shipping_modules() > 0) || ($free_shipping == true)) {

            if (isset($_POST['shipping']) && strpos($_POST['shipping'], '_')) {
                /**
                 * check to be sure submitted data hasn't been tampered with
                 */
                if ($_POST['shipping'] == 'free_free' && ($order->content_type != 'virtual' && !$pass)) {
                    $quote['error'] = 'Invalid input. Please make another selection.';
                } else {
                    $_SESSION['shipping'] = $_POST['shipping_method_id'];
                }
                list($module, $method) = explode('_', $_SESSION['shipping']);
                //			if (isset($GLOBALS[$module]) && is_object($GLOBALS[$module]))
                if ((isset($GLOBALS[$module]) && is_object($GLOBALS[$module])) || ($_SESSION['shipping'] == 'free_free')) {
                    if ($_SESSION['shipping'] == 'free_free') {
                        $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
                        $quote[0]['methods'][0]['cost'] = '0';
                    } else {
                        $quote = $shipping_modules->quote($method, $module);
                    }
                    if (isset($quote['error'])) {
                        $_SESSION['shipping'] = '';
                    } else {
                        if ((isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost']))) {
                            $_SESSION['shipping'] = array('id' => $_SESSION['shipping'],
                                'title' => (($free_shipping == true) ? $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
                                'cost' => $quote[0]['methods'][0]['cost']);
                        }
                    }
                } else {
                    $_SESSION['shipping'] = false;
                    // TODO 跳转到支付界面 FILENAME_CHECKOUT_PAYMENT
                }
            }
        } else {
            $_SESSION['shipping'] = false;
            // TODO 跳转到支付界面 FILENAME_CHECKOUT_PAYMENT
        }
    }
    KCLogger::Log('Checkout Flow: 10');
    $shipping_modules = new shipping;
    // 获取所有可用的送货报价
    $quotes = $shipping_modules->quote();
    KCLogger::Log('Checkout Flow: 11');
    // check that the currently selected shipping method is still valid (in case a zone restriction has disabled it, etc)
    if (isset($_SESSION['shipping']) && $_SESSION['shipping'] != FALSE && $_SESSION['shipping'] != '') {
        $checklist = array();
        foreach ($quotes as $key => $val) {
            foreach ($val['methods'] as $key2 => $method) {
                $checklist[] = $val['id'] . '_' . $method['id'];
            }
        }
        $checkval = (is_array($_SESSION['shipping']) ? $_SESSION['shipping']['id'] : $_SESSION['shipping']);
        if (!in_array($checkval, $checklist)) {
            $_SESSION['shipping'] = false;
            // $messageStack->add('checkout_shipping', ERROR_PLEASE_RESELECT_SHIPPING_METHOD, 'error');
        }
    }
    KCLogger::Log('Checkout Flow: 12');
    // 如果没有被选中的送货地址， 自动选择最便宜的方式
    if (!$_SESSION['shipping'] || ( $_SESSION['shipping'] && ($_SESSION['shipping'] == false) && (zen_count_shipping_modules() > 1) ))
        $_SESSION['shipping'] = $shipping_modules->cheapest();

    $order = new order;
    $returnResults = array();
    KCLogger::Log('Checkout Flow: 14');
    if ($isVirtual) {
        $returnResults['shipping_address'] = null;
        $returnResults['need_shipping_address'] = false;
    } else {
        $returnResults['shipping_address'] = kancart_user_address_get($_SESSION['sendto']);
        if (!$returnResults['shipping_address']) {
            $returnResults['shipping_address'] = null;
            $returnResults['need_shipping_address'] = true;
        } else {
            $returnResults['need_shipping_address'] = false;
        }
    }

    $returnResults['billing_address'] = kancart_user_address_get($_SESSION['billto']);
    if (!$returnResults['billing_address']) {
        $returnResults['billing_address'] = null;
        $returnResults['need_billing_address'] = true;
    } else {
        $returnResults['need_billing_address'] = false;
    }
    if ($isVirtual == true) {
        $returnResults['need_select_shipping_method'] = false;
    } else {
        if (isset($_SESSION['shipping']) && $_SESSION['shipping'] != false && $_SESSION['shipping'] != '') {
            $returnResults['need_select_shipping_method'] = false;
        } else {
            $returnResults['need_select_shipping_method'] = true;
        }
    }

    $review_orders = array();
    $reviewOrder = new KC_ReviewOrder();
    $cart = kancart_shoppingcart_get();
    $reviewOrder->cart_items = $cart['cart_items'];

    $reviewOrder->shipping_methods = kancart_shoppingcart_shippingmethods_get($free_shipping);
    if (isset($_SESSION['shipping'])) {
        $reviewOrder->selected_shipping_method_id = $_SESSION['shipping']['id'];
    }
    $review_orders[] = $reviewOrder;
    $returnResults['review_orders'] = $review_orders;
    $returnResults['is_virtual'] = $isVirtual;
    $returnResults['price_infos'] = kancart_shoppingcart_checkout_priceinfos_get();
    $returnResults['payment_methods'] = array();
    KCLogger::Log('Checkout Flow: 15');

    $total = 0;
    foreach ($returnResults['price_infos'] as $priceInfo) {
        if ($priceInfo->type == 'total') {
            $total = $priceInfo->price;
            break;
        }
    }
    KCLogger::Log('Checkout Flow: $total: ' . $total);
    if ($total == 0) {
        KCLogger::Log('Checkout Flow: 16-1');
        $lang_file = zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/', 'freecharger.php', 'false');
        if (@file_exists($lang_file)) {
            include_once($lang_file);
        }
        KCLogger::Log('Checkout Flow: 16-2: ' . DIR_WS_MODULES . 'payment/' . 'freecharger.php');
        require_once DIR_WS_MODULES . 'payment/' . 'freecharger.php';
        KCLogger::Log('Checkout Flow: 16-3');
        $freecharger = new freecharger;
        KCLogger::Log('Checkout Flow: 16-4');
        $freecharger->update_status();
        KCLogger::Log('Checkout Flow: 16-5');
        if ($freecharger->enabled) {
            $returnResults['payment_methods'][] = array('pm_id' => 'freecharger',
                'pm_title' => $freecharger->title,
                'pm_code' => 'freecharger',
                'description' => $freecharger->description,
                'img_url' => '');
        }
    } else {
        // TODO 获取可用的 PayPal 支付方式
        if (defined('MODULE_PAYMENT_PAYPALWPP_STATUS')) {
            // PayPalEC installed
            $lang_file = zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/', 'paypalwpp.php', 'false');
            if (@file_exists($lang_file)) {
                include_once($lang_file);
            }
            require_once 'kancartplugin/PayPal/PayPalWPP.php';
            $paypalwpp = new PayPalWPP();
            $paypalwpp->update_status();
            if ($paypalwpp->enabled) {
                $returnResults['payment_methods'][] = array('pm_id' => $paypalwpp->code,
                    'pm_title' => $paypalwpp->title,
                    'pm_code' => $paypalwpp->code,
                    'description' => $paypalwpp->description,
                    'img_url' => '');
            }
        } else if (defined('MODULE_PAYMENT_PAYPAL_STATUS')) {
            // PayPalWPS installed
            $lang_file = zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/', 'paypal.php', 'false');
            if (@file_exists($lang_file)) {
                include_once($lang_file);
            }
            require_once 'kancartplugin/PayPal/PayPalWPS.php';
            $paypalwps = new PayPalWPS();
            $paypalwps->update_status();
            if ($paypalwps->enabled) {
                $returnResults['payment_methods'][] = array('pm_id' => $paypalwps->code,
                    'pm_title' => $paypalwps->title,
                    'pm_code' => $paypalwps->code,
                    'description' => MODULE_PAYMENT_PAYPAL_ACCEPTANCE_MARK_TEXT, //$paypalwps->description,
                    'img_url' => MODULE_PAYMENT_PAYPAL_MARK_BUTTON_IMG);
            }
        }
    }
    KCLogger::Log('$returnResults' . json_encode($returnResults));
    return $returnResults;
}

// cls_ShoppingCart.php end