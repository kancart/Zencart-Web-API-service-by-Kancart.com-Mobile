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

class KC_Language {

    var $language_id;
    var $language_code;
    var $language_name;
    var $language_text;
    var $sort_order;

    function KC_Language() {
        
    }

}

/**
 * 获取支持的语言
 * @global KC_Response $kcResponse
 * @global QueryFactory $db 
 */
function kancart_languages_get() {
    Global $kcResponse, $db;
    $language_array = array();
    $languages_query = "select languages_id, name, code, image, directory
                          from " . TABLE_LANGUAGES . " 
                          order by sort_order";

    $languages = $db->Execute($languages_query);

    while (!$languages->EOF) {
        $language = new KC_Language();

        $language->language_id = $languages->fields['languages_id'];
        $language->language_code = $languages->fields['code'];
        $language->language_name = $languages->fields['name'];
        $language->language_text = $languages->fields['name'];
        $language->sort_order = $languages->fields['languages_id'];
        $language_array[] = $language;
        $languages->MoveNext();
    }
    if (count($language_array) == 0) {
        // TODO 处理没有支持的语言
        $kcResponse->ErrorBack('', 'no languages');
    } else {
        $back = array('languages' => $language_array);
        $kcResponse->DataBack($back);
    }
}

// cls_Language.php end