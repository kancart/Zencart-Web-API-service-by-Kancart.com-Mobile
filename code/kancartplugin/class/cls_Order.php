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
require_once 'kancartplugin/class/cls_ShoppingCart.php';
require_once 'kancartplugin/class/cls_OrderStatus.php';
require_once 'kancartplugin/class/cls_Country.php';
require_once 'kancartplugin/class/cls_Zone.php';

class KC_Order {

    var $order_id;
    var $display_id;
    var $uname;
    var $currency;
    var $shipping_address;
    var $billing_address;
    var $payment_method;
    var $shipping_method;
    var $shipping_insurance;
    var $coupon;
    var $price_infos = array();
    var $order_items = array();
    var $order_status = array();
    var $last_status_id;
    var $order_tax;
    var $order_date_start;
    var $order_date_finish;
    var $order_date_purchased;

    function KC_Order($order_id) {
        global $db;
        $currencies = new currencies();

        $this->order_id = zen_db_prepare_input($order_id);

        $order_query = "select customers_id, customers_name, customers_company,
                               customers_street_address, customers_suburb, customers_city,
                               customers_postcode, customers_state, customers_country,
                               customers_telephone, customers_email_address, customers_address_format_id,
                               delivery_name, delivery_company, delivery_street_address, delivery_suburb,
                               delivery_city, delivery_postcode, delivery_state, delivery_country,
                               delivery_address_format_id, billing_name, billing_company,
                               billing_street_address, billing_suburb, billing_city, billing_postcode,
                               billing_state, billing_country, billing_address_format_id,
                               payment_method, payment_module_code, shipping_method, shipping_module_code,
                               coupon_code, cc_type, cc_owner, cc_number, cc_expires, currency, currency_value,
                               date_purchased, orders_status, last_modified, order_total, order_tax, ip_address
                        from " . TABLE_ORDERS . "
                        where orders_id = '" . (int) $this->order_id . "'";
        $order = $db->Execute($order_query);
        if (!$order->EOF) {
            // 获取用于显示的订单ID
            $this->display_id = $this->order_id;
            // 获取支付时的货币
            $this->currency = $order->fields['currency'];
            // 获取当前的订单状态ID
            $this->last_status_id = $order->fields['orders_status'];
            // TODO 获取订单的附加税 （考虑移除）
            $this->order_tax = $order->fields['order_tax'];

            // 获取运输方式
            $this->shipping_method = new KC_ShippingMethod();
            $this->shipping_method->sm_id = $order->fields['shipping_module_code'];
            $this->shipping_method->sm_code = $order->fields['shipping_module_code'];
            $this->shipping_method->title = $order->fields['shipping_method'];
            $this->shipping_method->currency = $this->currency;

            // 获取支付方式
            $this->payment_method = new KC_PaymentMethod();
            $this->payment_method->pm_id = $order->fields['payment_module_code'];
            $this->payment_method->title = $order->fields['payment_method'];
            $this->payment_method->description = '';

            // TODO 转成Coupon 对象
            $this->coupon = $order->fields['coupon_code'];

            $currency_value = $order->fields['currency_value'];

            // 获取运输地址
            $this->shipping_address = new KC_Address();
            $this->shipping_address->address1 = $order->fields['delivery_street_address'];
            $this->shipping_address->address2 = $order->fields['delivery_suburb'];
            $this->shipping_address->firstname = $order->fields['delivery_name'];
            $this->shipping_address->lastname = '';
            $this->shipping_address->city = $order->fields['delivery_city'];
            $this->shipping_address->postcode = $order->fields['delivery_postcode'];
            $this->shipping_address->state = $order->fields['delivery_state'];
            $zones = kancart_get_zone_by_name($this->shipping_address->state);
            if (count($zones) > 0) {
                $zoneObj = $zones[0];
                $this->shipping_address->zone_name = $zoneObj->zone_name;
                $this->shipping_address->zone_code = $zoneObj->zone_code;
                $this->shipping_address->zone_id = $zoneObj->zone_id;
            }

            $this->shipping_address->country_name = $order->fields['delivery_country'];
            $countries = kancart_get_country_by_name($this->shipping_address->country_name);
            if (count($countries) > 0) {
                $countryObj = $countries[0];
                $this->shipping_address->country_name = $countryObj->country_name;
                $this->shipping_address->country_code = $countryObj->country_iso_code_2;
                $this->shipping_address->country_id = $countryObj->country_id;
            }
            $this->shipping_address->telephone = $order->fields['customers_telephone'];

            // 如果运输地址没有名字 也没有街道地址  那么就是没有送货地址
            if (empty($this->shipping_address->firstname) && empty($this->shipping_address->address1)) {
                $this->shipping_address = false;
            }

            // 获取账单地址
            $this->billing_address = new KC_Address();
            $this->billing_address->address1 = $order->fields['billing_street_address'];
            $this->billing_address->address2 = $order->fields['billing_suburb'];
            $this->billing_address->firstname = $order->fields['billing_name'];
            $this->billing_address->lastname = '';
            $this->billing_address->city = $order->fields['billing_city'];
            $this->billing_address->postcode = $order->fields['billing_postcode'];
            $this->billing_address->state = $order->fields['billing_state'];
            $this->billing_address->country_name = $order->fields['billing_country'];
            $countries = kancart_get_country_by_name($this->billing_address->country_name);
            if (count($countries) > 0) {
                $countryObj = $countries[0];
                $this->billing_address->country_code = $countryObj->country_iso_code_2;
                $this->billing_address->country_id = $countryObj->country_id;
            }
            $this->billing_address->telephone = $order->fields['customers_telephone'];

            // 获取订单的所有价格信息
            $totals_query = "select title, text, class, value, sort_order
                         from " . TABLE_ORDERS_TOTAL . "
                         where orders_id = '" . (int) $order_id . "'
                         order by sort_order";

            $totals = $db->Execute($totals_query);
            while (!$totals->EOF) {
                $priceInfo = new KC_PriceInfo();
                $priceInfo->name = $totals->fields['title'];
                $priceInfo->price = $currencies->value($totals->fields['value'], false) * $currency_value;
                $priceInfo->home_currency_price = $currencies->value($totals->fields['value'], false);
                $priceInfo->type = $totals->fields['class'];
                $priceInfo->currency = $this->currency;
                if ($priceInfo->type == 'ot_total') {
                    $priceInfo->type = 'total';
                } else if ($priceInfo->type == 'ot_shipping') {
                    $priceInfo->name = "Shipping Cost:";
                    $priceInfo->type = "shipping";
                    $this->shipping_method->price = $currencies->value($totals->fields['value'], false) * $currency_value;
                } else if ($priceInfo->type == 'ot_tax') {
                    $priceInfo->type = "tax";
                }
                $priceInfo->position = $totals->fields['sort_order'];
                array_push($this->price_infos, $priceInfo);
                $totals->MoveNext();
            }

            // 获取订单状态历史
            $status_history_query = "SELECT osh.orders_status_id,
					    osh.date_added,
					    osh.comments,
				            os.orders_status_name
                                     FROM " . TABLE_ORDERS_STATUS . " AS os
                                     INNER JOIN " . TABLE_ORDERS_STATUS_HISTORY . " AS osh
                                     ON os.orders_status_id = osh.orders_status_id
                                     WHERE osh.orders_id = '" . (int) $this->order_id . "' AND
                                           os.language_id = '" . (int) $_SESSION['languages_id'] . "'
                                     ORDER BY
                                     osh.date_added DESC";
            KCLogger::Log($status_history_query);
            $status_history_list = $db->Execute($status_history_query);
            $this->order_status = array();
            $i = 1;

            while (!$status_history_list->EOF) {
                $orderStatus = new KC_OrderStatus();
                $orderStatus->status_id = $status_history_list->fields['orders_status_id'];
                $orderStatus->status_name = $status_history_list->fields['orders_status_name'];
                $orderStatus->display_text = $status_history_list->fields['orders_status_name'];
                $orderStatus->date_added = $status_history_list->fields['date_added'];
                $orderStatus->comments = nl2br($status_history_list->fields['comments']);
                $orderStatus->position = $i;
                $this->order_status[] = $orderStatus;
                $i++;
                $status_history_list->MoveNext();
            }

            // 获取订单商品
            $orders_products_query = "select op.orders_products_id, op.products_id, op.products_name,
                                             op.products_model, op.products_price, op.products_tax,
                                             op.products_quantity, op.final_price,
                                             op.onetime_charges,
                                             op.products_priced_by_attribute, op.product_is_free, op.products_discount_type,
                                             op.products_discount_type_from, p.products_image
                                      from " . TABLE_ORDERS_PRODUCTS . " AS op
                                      INNER JOIN " . TABLE_PRODUCTS . " AS p
                                                                      ON op.products_id = p.products_id
                                      where orders_id = '" . (int) $order_id . "'
                                      order by orders_products_id";

            $orders_products = $db->Execute($orders_products_query);
            $this->order_items = array();
            while (!$orders_products->EOF) {
                // convert quantity to proper decimals - account history
                if (QUANTITY_DECIMALS != 0) {
                    $fix_qty = $orders_products->fields['products_quantity'];
                    switch (true) {
                        case (!strstr($fix_qty, '.')):
                            $new_qty = $fix_qty;
                            break;
                        default:
                            $new_qty = preg_replace('/[0]+$/', '', $orders_products->fields['products_quantity']);
                            break;
                    }
                } else {
                    $new_qty = $orders_products->fields['products_quantity'];
                }

                $new_qty = round($new_qty, QUANTITY_DECIMALS);

                if ($new_qty == (int) $new_qty) {
                    $new_qty = (int) $new_qty;
                }

                $product = new KC_OrderItem();
                $product->order_item_id = $orders_products->fields['orders_products_id'];
                $product->item_id = $orders_products->fields['products_id'];
                $product->display_id = $orders_products->fields['products_id'];
                if ($orders_products->fields['products_model'] && strlen($orders_products->fields['products_model'])> 0) {
                    $product->order_item_key = $orders_products->fields['products_model'];
                } else {
                    $product->order_item_key = 'ITEM_' . $product->item_id;
                }

                $product->item_title = $orders_products->fields['products_name'];
                $product->price = $currencies->value($orders_products->fields['products_price'], false) * $currency_value;
                
                $product->home_currency_price = $currencies->value($orders_products->fields['products_price'], false);
                
                $product->final_price = $currencies->value($orders_products->fields['final_price'], false) * $currency_value;
                $product->item_tax = $orders_products->fields['products_tax'];
                $product->qty = $orders_products->fields['products_quantity'];

                // TODO 没有相关属性能表示该商品是否是免运费
                $product->post_free = false;

                $product->virtual_flag = false;

                $productsImage = $orders_products->fields['products_image'];
                if (isEmptyString($productsImage)) {
                    $productsImage = 'no_picture.gif';
                }
                $product->thumbnail_pic_url = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $productsImage;

                if (is_int(strpos($productsImage, ','))) {
                    $imgsrcs = split(',', $productsImage);
                    $product->thumbnail_pic_url = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $imgsrcs[0];
                }

                // 获取商品选择的属性
                $product->skus = '';

                // TODO 转换属性成字符串
                $product->display_skus = '';

                $attributes_query = "select products_options_id, products_options_values_id, products_options, products_options_values,
                              options_values_price, price_prefix from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
                               where orders_id = '" . (int) $order_id . "'
                               and orders_products_id = '" . (int) $orders_products->fields['orders_products_id'] . "'";

                $attributes = $db->Execute($attributes_query);
                if ($attributes->RecordCount()) {
                    while (!$attributes->EOF) {
                        $product->display_skus .= $attributes->fields['products_options'] . ":" . $attributes->fields['products_options_values'] . "; ";
                        //					$this->products[$order_items]['attributes'][$subindex] = array('option' => $attributes->fields['products_options'],
                        //                                                                   'value' => $attributes->fields['products_options_values'],
                        //                                                                   'option_id' => $attributes->fields['products_options_id'],
                        //                                                                   'value_id' => $attributes->fields['products_options_values_id'],
                        //                                                                   'prefix' => $attributes->fields['price_prefix'],
                        //                                                                   'price' => $attributes->fields['options_values_price']);
                        //
						//					$subindex++;
                        $attributes->MoveNext();
                    }
                }

                // $this->info['tax_groups']["{$this->$order_items[$index]['tax']}"] = '1';
                array_push($this->order_items, $product);
                $orders_products->MoveNext();
            }
        }
    }

}

class KC_PaymentMethod {

    var $pm_id;
    var $title;
    var $description;

    function KC_PaymentMethod() {
        
    }

}

class KC_OrderItem {

    var $order_item_id;
    var $item_id;
    var $display_id;
    var $order_item_key;
    var $display_skus;
    var $skus;
    var $item_title;
    var $thumbnail_pic_url;
    var $qty;
    var $price;
    var $final_price;
    var $home_currency_price;
    var $item_tax;
    var $shipping_method;
    var $post_free;
    var $virtual_flag;

    function KC_OrderItem() {
        
    }

}

/**
 * 获取对应订单状态的订单数量
 */
function kancart_orders_count() {
    Global $kcResponse, $db;
    if (kancart_validate_user()) {
        $orders_total = zen_count_customer_orders();
        $returnResults = array();
        $count_array = array();
        $a_count = array('status_ids' => 'all', 'status_name' => 'All Orders', 'count' => $orders_total);
        $count_array[] = $a_count;

        $returnResults['order_counts'] = $count_array;
        $kcResponse->DataBack($returnResults);
    } else {
        // TODO 用户不存在或者未登录
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 * 获取订单列表
 */
function kancart_orders_get() {
    Global $kcResponse, $db;
    if (kancart_validate_user()) {
        $page_no = 1;
        $page_size = 10;
        $status_name = "All Orders";

        if (isset($_POST['page_no']) && (int) $_POST['page_no'] > 0) {
            $page_no = $_POST['page_no'];
        }
        if (isset($_POST['page_size']) && (int) $_POST['page_size'] > 0) {
            $page_size = $_POST['page_size'];
        }
        // 单页数量不能超过30条
        $page_size = $page_size > 30 ? 30 : $page_size;

        $returnResult = array();

        // 订单总数
        $orders_total = zen_count_customer_orders();
        $returnResult['total_results'] = $orders_total;

        $order_array = array();
        if ($orders_total > 0) {
            $history_query = "SELECT o.orders_id
                        FROM   " . TABLE_ORDERS . " o
                        WHERE      o.customers_id = :customersID
                        ORDER BY   orders_id DESC ";
            $limitStr = " LIMIT " . ($page_no > 1 ? ($page_no - 1) * $page_size . "," : '') . $page_size;
            $history_query .= $limitStr;
            $history_query = $db->bindVars($history_query, ':customersID', $_SESSION['customer_id'], 'integer');
            $history = $db->Execute($history_query);

            while (!$history->EOF) {
                $order = new KC_Order($history->fields['orders_id']);
                $order_array[] = $order;
                $history->moveNext();
            }
        }
        $returnResult['orders'] = $order_array;
        $kcResponse->DataBack($returnResult);
    } else {
        // TODO 用户不存在或者未登录
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 * 获取当个订单详情
 */
function kancart_order_get() {
    Global $kcResponse, $db;
    $order_id = $_POST['order_id'];

    if (kancart_order_isExist($order_id)) {
        $order = new KC_Order($order_id);
        if ($order instanceof KC_Order) {
            $kcResponse->DataBack(array('order' => $order));
        } else {
            // TODO 确定是否使用系统异常并确定错误代码
            $kcResponse->ErrorBack('0x0011');
        }
    } else {
        // 确定使用的错误代码
        $kcResponse->ErrorBack('0x6002');
    }
}

function kancart_order_isExist($order_id) {
    global $db, $messageStack;
    $zc_check_order = $db->Execute("SELECT orders_id from " . TABLE_ORDERS . " WHERE orders_id=" . (int) $order_id);
    if ($zc_check_order->RecordCount() <= 0) {
        return false;
    }
    return true;
}

// cls_Order.php end