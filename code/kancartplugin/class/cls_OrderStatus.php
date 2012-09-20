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

class KC_OrderStatus {

    var $status_id;
    var $status_name;
    var $display_text;
    var $language_id;
    var $date_added;
    var $comments;
    var $position;

    function KC_OrderStatus() {
        
    }

}

/**
 * 获取订单的所有可能状态
 * @global KC_Response $kcResponse
 * @global QueryFactory $db 
 */
function kancart_orderstatuses_get() {
    Global $kcResponse, $db;
    $order_statuses_query = "select * from " . TABLE_ORDERS_STATUS;
    //. " WHERE language_id = '" . (int)$_SESSION['languages_id'] . "' "  ;
    $order_statuses = array();
    $orderstatuses_values = $db->Execute($order_statuses_query);
    while (!$orderstatuses_values->EOF) {
        $orderStatus = new KC_OrderStatus();
        $orderStatus->status_id = $orderstatuses_values->fields['orders_status_id'];
        $orderStatus->display_text = $orderstatuses_values->fields['orders_status_name'];
        $orderStatus->status_name = $orderstatuses_values->fields['orders_status_name'];
        $orderStatus->language_id = $orderstatuses_values->fields['language_id'];
        $order_statuses[] = $orderStatus;
        $orderstatuses_values->MoveNext();
    }
    if (count($order_statuses) == 0) {
        // TODO 处理没有订单状态
        $kcResponse->ErrorBack('', 'no order statuses');
    } else {
        $back = array('order_statuses' => $order_statuses);
        $kcResponse->DataBack($back);
    }
}

// cls_OrderStatus.php