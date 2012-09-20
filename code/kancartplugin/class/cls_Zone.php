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

/**
 * 地区类
 * @author Jiawei
 *
 */
class KC_Zone {

    var $zone_id = '';
    var $country_id = '';
    var $zone_code = '';
    var $zone_name = '';

    function KC_Zone() {
        
    }

}

/**
 * 获取支持的地区列表
 * @global KC_Response $kcResponse
 * @global QueryFactory $db 
 */
function kancart_zones_get() {
    Global $kcResponse, $db;
    $zones_array = array();
    $zones_query .= "select * from " . TABLE_ZONES;
    $zones_query .= " order by zone_id";

    $zonesValues = $db->Execute($zones_query);

    while (!$zonesValues->EOF) {
        $zone = new KC_Zone();
        $zone->zone_id = $zonesValues->fields ['zone_id'];
        $zone->country_id = $zonesValues->fields ['zone_country_id'];
        $zone->zone_code = $zonesValues->fields ['zone_code'];
        $zone->zone_name = $zonesValues->fields ['zone_name'];

        $zones_array[] = $zone;
        $zonesValues->MoveNext();
    }
    if (count($zones_array) == 0) {

        $kcResponse->ErrorBack('', 'no zones');
    } else {
        $back = array('zones' => $zones_array);
        $kcResponse->DataBack($back);
    }
}

function kancart_get_zone_by_name($zone_name = '') {
    Global $kcResponse, $db;
    $zones_array = array();
    $zones = "select * from " . TABLE_ZONES . " where zone_name='" . zen_db_prepare_input($zone_name) . "' limit 1";
    $zones_values = $db->Execute($zones);
    while (!$zones_values->EOF) {
        $zone = new KC_Zone();
        $zone->zone_id = $zones_values->fields ['zone_id'];
        $zone->country_id = $zones_values->fields ['zone_country_id'];
        $zone->zone_code = $zones_values->fields ['zone_code'];
        $zone->zone_name = $zones_values->fields ['zone_name'];
        $zones_array[] = $zone;
        $zones_values->MoveNext();
    }
    KCLogger::Log('get zone by name: ' . json_encode($zones_array));
    return $zones_array;
}

// cls_Zone.php end