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

class KC_Currency {
    
    var $currency_code = '';
    var $currency_symbol = '';
    var $currency_symbol_right = false;
    var $decimal_symbol = '';
    var $group_symbol = '';
    var $decimal_places = '';
    var $description = '';

    function KC_Currency() {
        
    }

}

function kancart_currencies_get() {
    Global $kcResponse, $db;
    $currencies_array = array();
    $currencies = "select * from " . TABLE_CURRENCIES;

    $currencies_values = $db->Execute($currencies);

    while (!$currencies_values->EOF) {
        $currency = new KC_Currency();
        $currency->currency_code = $currencies_values->fields['code'];
        $currency->description = $currencies_values->fields['title'];
        
        $symbolIsLeft = true;
        if (isEmptyString($currencies_values->fields['symbol_left'])) {
            $symbolIsLeft = false;
        }
        
        $currency->currency_symbol = $symbolIsLeft? $currencies_values->fields['symbol_left']: $currencies_values->fields['symbol_right'];
        $currency->currency_symbol_right = !$symbolIsLeft;
        
        $currency->decimal_symbol = $currencies_values->fields['decimal_point'];
        $currency->group_symbol = $currencies_values->fields['thousands_point'];
        $currency->decimal_places = $currencies_values->fields['decimal_places'];
        $currencies_array[] = $currency;
        $currencies_values->MoveNext();
    }
    if (count($currencies_array) == 0) {

        $kcResponse->ErrorBack('', 'no currencies');
    } else {
        $back = array('currencies' => $currencies_array);
        $kcResponse->DataBack($back);
    }
}

// cls_Currency.php