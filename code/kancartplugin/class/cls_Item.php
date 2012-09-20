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
require_once 'kancartplugin/class/cls_TradeRate.php';

class KC_Item {

    var $item_id = '';
    var $skus = array();
    var $item_url = '';
    var $item_title = '';
    var $description_url = '';
    var $detail_description = '';
    var $thumb_description = '';
    var $cid = '';
    var $qty = '';
    var $item_status = '';
    var $stuff_status = '';
    var $original_price = '';
    var $price = '';
    var $price_range = '';
    var $price_list = '';
    var $prices = array();
    var $currency = '';
    var $is_discount = '';
    var $is_specials = '';
    var $is_recommended = '';
    var $is_promotions = '';
    var $is_price_reduction = '';
    var $is_sale = '';
    var $post_free = '';
    var $auto_post = '';
    var $virtual_flag = '';
    var $item_imgs = array();
    var $score = '';
    var $volume = '';
    var $thumbnail_pic_url = '';
    var $main_pic_url = '';
    var $shipping_fee = '';
    var $discount = '';
    var $rating_count = '';
    var $rating_score = '';
    var $qty_order_min = 1;
    var $qty_order_max = 0;
    var $qty_order_units = 1;
    var $qty_allow_mixed = 1;
    var $is_free = 0;

    function KC_Item() {
        
    }

}

class KC_Itemimg {

    var $img_id = '';
    var $img_url = '';
    var $position = '';

    function KC_Itemimg() {
        
    }

}

class KC_Sku {

    var $sku_id = '';
    var $name = '';
    var $mode = '';
    var $allow_blank = '';
    var $values = array();
    var $parent_id = '';
    var $custom_name = '';
    var $custom_price = '';
    var $custom_text = '';
    var $child = array();

    function KC_Sku() {
        
    }

}

class KC_SkuValue {

    var $value_id = '';
    var $sku_id = '';
    var $name = '';
    var $price = '';
    var $qty = '';
    var $is_default = false;

    function KC_SkuValue() {
        
    }

}

/**
 * 查询单个商品
 * @param string $product_id
 * @return Item 商品详细信息
 */
function kancart_item_get($product_id) {
    Global $kcResponse, $db, $template, $template_dir, $current_page_base, $language_page_directory, $currencies;
    $language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';
    require_once DIR_WS_LANGUAGES . $_SESSION['language'] . '.php';
    $current_page_base = 'product_info';
    require_once DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

    $sql = "select p.*, pd.*
            from   " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd
            where  p.products_status = '1'
            and    p.products_id = '" . $product_id . "'
            and    pd.products_id = p.products_id
	    and    pd.language_id = '" . (int) $_SESSION['languages_id'] . "'";

    $product_info = $db->Execute($sql);
    $item = null;
    if ($product_info->RecordCount() > 0) {
        while (!$product_info->EOF) {
            $item = turnItem($product_info, true);
            break;
        }
    }
    if ($item instanceof KC_Item) {
        $kcResponse->DataBack(array('item' => $item));
    } else {
        $kcResponse->ErrorBack('0x3003', TEXT_PRODUCT_NOT_FOUND);
    }
}

function kancart_get_products_display_price($product) {
    global $db, $currencies;
    $currentCurrenyValue = $currencies->currencies[$_SESSION['currency']]['value'];
    $products_id = $product->fields['products_id'];

    $displayPrices = array();
    $displayPrices['prices'] = array();
    $displayPrices['discount'] = 0;

    // 暂不支持必须登录或者需要权限才能查看价格的功能
    // 0 = normal shopping
    // 1 = Login to shop
    // 2 = Can browse but no prices
    // verify display of prices
//    switch (true) {
//        case (CUSTOMERS_APPROVAL == '1' and $_SESSION['customer_id'] == ''):
//            // customer must be logged in to browse
//            return array();
//            break;
//        case (CUSTOMERS_APPROVAL == '2' and $_SESSION['customer_id'] == ''): {
//                $normalPrice = array();
//                $normalPrice['title'] = TEXT_LOGIN_FOR_PRICE_PRICE;
//                $normalPrice['price'] = 0;
//                $normalPrice['style'] = 'need_login';
//                $displayPrices['prices'][] = $normalPrice;
//                // customer may browse but no prices
//                return $displayPrices;
//            } break;
//        case (CUSTOMERS_APPROVAL == '3' and TEXT_LOGIN_FOR_PRICE_PRICE_SHOWROOM != ''): {
//                $normalPrice = array();
//                $normalPrice['title'] = TEXT_LOGIN_FOR_PRICE_PRICE_SHOWROOM;
//                $normalPrice['price'] = 0;
//                $normalPrice['style'] = 'need_login';
//                $displayPrices['prices'][] = $normalPrice;
//                // customer may browse but no prices
//                return $displayPrices;
//            } break;
//        case ((CUSTOMERS_APPROVAL_AUTHORIZATION != '0' and CUSTOMERS_APPROVAL_AUTHORIZATION != '3') and $_SESSION['customer_id'] == ''): {
//                $normalPrice = array();
//                $normalPrice['title'] = TEXT_AUTHORIZATION_PENDING_PRICE;
//                $normalPrice['price'] = 0;
//                $normalPrice['style'] = 'need_login';
//                $displayPrices['prices'][] = $normalPrice;
//                // customer must be logged in to browse
//                return $displayPrices;
//            } break;
//        case ((CUSTOMERS_APPROVAL_AUTHORIZATION != '0' and CUSTOMERS_APPROVAL_AUTHORIZATION != '3') and $_SESSION['customers_authorization'] > '0'): {
//                $normalPrice = array();
//                $normalPrice['title'] = TEXT_AUTHORIZATION_PENDING_PRICE;
//                $normalPrice['price'] = 0;
//                $normalPrice['style'] = 'need_login';
//                $displayPrices['prices'][] = $normalPrice;
//                // customer must be logged in to browse
//                return $displayPrices;
//            } break;
//        default:
//            // proceed normally
//            break;
//    }
//    if (STORE_STATUS != '0') {
//        if (STORE_STATUS == '1') {
//            $normalPrice = array();
//            $normalPrice['title'] = TEXT_AUTHORIZATION_PENDING_PRICE;
//            $normalPrice['price'] = 0;
//            $normalPrice['style'] = 'need_login';
//            $displayPrices['prices'][] = $normalPrice;
//            return $displayPrices;
//        }
//    }

    // $new_fields = ', product_is_free, product_is_call, product_is_showroom_only';
    $product_check = $db->Execute("select products_tax_class_id, products_price, products_priced_by_attribute, product_is_free, product_is_call, products_type from " . TABLE_PRODUCTS . " where products_id = '" . (int) $products_id . "'" . " limit 1");

    if ($product_check->fields['products_type'] == 3) {
        return $displayPrices;
    }

    // 价格显示规则
    // 如果有sale_price 则不显示 special_price


    $productTaxRate = zen_get_tax_rate($product_check->fields['products_tax_class_id']);

    $display_normal_price = zen_get_products_base_price($products_id);
    $display_special_price = zen_get_products_special_price($products_id, true);
    $display_sale_price = zen_get_products_special_price($products_id, false);

    KCLogger::Log('$display_normal_price: ' . $display_normal_price);
    KCLogger::Log('$display_special_price: ' . $display_special_price);
    KCLogger::Log('$display_sale_price: ' . $display_sale_price);

    if (SHOW_SALE_DISCOUNT_STATUS == '1' and ($display_special_price != 0 or $display_sale_price != 0)) {
        if ($display_sale_price) {
            if (SHOW_SALE_DISCOUNT == 1) {
                if ($display_normal_price != 0) {
                    $displayPrices['discount'] = number_format(100 - (($display_sale_price / $display_normal_price) * 100), SHOW_SALE_DISCOUNT_DECIMALS);
                } else {
                    // do nothing
                    // $displayPrices['discount'] = 0;
                }
            } else {
                // show as price
                $discountPrice = array();
                $discountPrice['title'] = PRODUCT_PRICE_DISCOUNT_PREFIX;
                $discountPrice['price'] = zen_add_tax(($display_normal_price - $display_special_price), $productTaxRate);
                $discountPrice['style'] = 'discount';
                $displayPrices['prices'][] = $discountPrice;
            }
        } else {
            if (SHOW_SALE_DISCOUNT == 1) {
                $displayPrices['discount'] = number_format(100 - (($display_special_price / $display_normal_price) * 100), SHOW_SALE_DISCOUNT_DECIMALS);
            } else {
                // show as price
                $discountPrice = array();
                $discountPrice['title'] = PRODUCT_PRICE_DISCOUNT_PREFIX;
                $discountPrice['price'] = zen_add_tax(($display_normal_price - $display_special_price), $productTaxRate);
                $discountPrice['style'] = 'discount';
                $displayPrices['prices'][] = $discountPrice;
            }
        }
    }

    $normalPrice = array();
    $normalPrice['title'] = 'Price: ';
    $normalPrice['price'] = zen_add_tax($display_normal_price, $productTaxRate);
    $normalPrice['style'] = 'normal';

    $specialPrice = array();
    $specialPrice['title'] = 'Price: '; //==================
    $specialPrice['price'] = zen_add_tax($display_special_price, $productTaxRate);
    $specialPrice['style'] = 'normal';

    $salePrice = array();
    $salePrice['title'] = 'Price: ';
    $salePrice['price'] = zen_add_tax($display_sale_price, $productTaxRate);
    $salePrice['style'] = 'normal';
    KCLogger::Log('prices  1');
    if ($display_special_price) {
        KCLogger::Log('prices  2');
        if (!($display_sale_price && $display_sale_price != $display_special_price)) {
            // clear show sale price;
            KCLogger::Log('prices  3');
            $salePrice = null;
        }
    } else {
        KCLogger::Log('prices  4');
        if ($display_sale_price) {
            KCLogger::Log('prices  5');
            // clear show special price;
            $specialPrice = null;
        } else {
            KCLogger::Log('prices  6');
            // clear show special price;
            // clear show sale price;
            $specialPrice = null;
            $salePrice = null;
        }
    }
    KCLogger::Log('prices  7');

    // base price, it will be used to calc the final price.
    $displayPrices['base_price'] = array();

    if ($normalPrice != null) {
        $normalPrice['title'] = 'Price: ';
        $displayPrices['base_price']['price'] = $normalPrice['price'];
        $displayPrices['prices'][] = $normalPrice;
        KCLogger::Log('add normalPrice');
    }
    if ($salePrice == null && $specialPrice != null) {
        if (count($displayPrices['prices']) > 0) {
            $displayPrices['prices'][count($displayPrices['prices']) - 1]['title'] = 'List Price: ';
            $displayPrices['prices'][count($displayPrices['prices']) - 1]['style'] = 'line-through';
        }
        $displayPrices['base_price']['price'] = $specialPrice['price'];
        $displayPrices['prices'][] = $specialPrice;
        KCLogger::Log('add specialPrice');
    }
    if ($salePrice != null) {
        if (count($displayPrices['prices']) > 0) {
            $displayPrices['prices'][count($displayPrices['prices']) - 1]['title'] = 'List Price: ';
            $displayPrices['prices'][count($displayPrices['prices']) - 1]['style'] = 'line-through';
        }
        $displayPrices['base_price']['price'] = $salePrice['price'];
        $displayPrices['prices'][] = $salePrice;
        KCLogger::Log('add salePrice');
    }


    // If Free, Show it
    if ($product_check->fields['product_is_free'] == '1') {
        // OTHER_IMAGE_PRICE_IS_FREE_ON
        // PRODUCTS_PRICE_IS_FREE_TEXT
        // OTHER_IMAGE_PRICE_IS_FREE
        if (count($displayPrices['prices']) > 0) {
            array_splice($displayPrices['prices'], count($displayPrices['prices']) - 1, 1);
        }
        $freePrice = array();
        $freePrice['title'] = PRODUCTS_PRICE_IS_FREE_TEXT;
        $freePrice['price'] = 0;
        $freePrice['style'] = 'free';
        $displayPrices['prices'][] = $freePrice;
        KCLogger::Log('add freePrice');
    }

    // If Call for Price, Show it
    if ($product_check->fields['product_is_call']) {
        // PRODUCTS_PRICE_IS_CALL_IMAGE_ON
        // PRODUCTS_PRICE_IS_CALL_FOR_PRICE_TEXT
        // OTHER_IMAGE_CALL_FOR_PRICE
        if (count($displayPrices['prices']) > 0) {
            array_splice($displayPrices['prices'], count($displayPrices['prices']) - 1, 1);
        }
        $callPrice = array();
        $callPrice['title'] = PRODUCTS_PRICE_IS_CALL_FOR_PRICE_TEXT;
        $callPrice['price'] = 0;
        $callPrice['style'] = 'call';
        $displayPrices['prices'][] = $callPrice;
        KCLogger::Log('add callPrice');
    }

    foreach ($displayPrices['prices'] as $key => $value) {
        $displayPrices['prices'][$key]['price'] = $currencies->value($displayPrices['prices'][$key]['price'], false) * $currentCurrenyValue;
    }

    $displayPrices['base_price']['price'] = $currencies->value($displayPrices['base_price']['price'], false) * $currentCurrenyValue;

    $tierPrices = get_item_discount_quantity($product);
    $displayPrices['tier_prices'] = $tierPrices;
    KCLogger::Log('$displayPrices: ' . json_encode($displayPrices));
    return $displayPrices;
}

/**
 * 封装Item
 * @param array $product_info 商品信息的数组
 * @param boolean $getSkus (option, default false) 是否获取skus
 * @param boolean $getImgs (option, default false) 是否获取商品多图
 * @return Item
 */
function turnItem($product_info, $getDetial = false) {
    $item = new KC_Item();
    $item->item_id = $product_info->fields['products_id'];
    $item->cid = $product_info->fields['categories_id'];

    $item->item_url = $product_info->fields['products_url'];
    $item->item_title = $product_info->fields['products_name'];
    $item->detail_description = (!preg_match('/(<br|<p|<div|<dd|<li|<span)/i', $product_info->fields['products_description']) ? nl2br($product_info->fields['products_description']) : $product_info->fields['products_description']);
    $item->detail_description = preg_replace('/(\<img[^\<^\>]+src=\"|\')\.{1,2}\//i', '$1/', $item->detail_description);
    $item->detail_description = preg_replace('/(\<img[^\<^\>]+src=)(\"|\')(\.\.)?(\/[^\"^\']+)(\"|\')([^\<^\>]*\>)/i', '$1$2' . ((isset($_SERVER['HTTPS ']) && $_SERVER['HTTPS '] != 'off ') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '$4$5$6', $item->detail_description);

    $item->cid = $product_info->fields['master_categories_id'];
    $item->qty = $product_info->fields['products_quantity'];

//    $item->qty_order_min = $product_info->fields['products_quantity_order_min'];
//    $item->qty_order_max = $product_info->fields['products_quantity_order_max'];
//    $item->qty_order_units = $product_info->fields['products_quantity_order_units'];
//    $item->qty_allow_mixed = $product_info->fields['products_quantity_mixed'];
    $productsImage = $product_info->fields['products_image'];
    if (isEmptyString($productsImage)) {
        $product_info->fields['products_image'] = 'no_picture.gif';
    }

    $item->thumbnail_pic_url = kancart_get_item_main_image($product_info, 'base');
    $item->main_pic_url = kancart_get_item_main_image($product_info, 'large');
    KCLogger::Log('$item->thumbnail_pic_url: ' . $item->thumbnail_pic_url);
    KCLogger::Log('$item->main_pic_url: ' . $item->main_pic_url);
    kancart_get_item_images($product_info, $item);

    if (count($item->item_imgs) == 0) {
        $itemImg = new KC_Itemimg();
        $itemImg->img_id = $item->item_id . "_1";
        $itemImg->position = 1;
        $itemImg->img_url = $item->thumbnail_pic_url;
        $item->item_imgs[] = $itemImg;
    }

    if ($product_info->fields['products_quantity'] <= 0) {
        $item->item_status = 'outofstock';
    } else {
        $item->item_status = 'onsale';
    }

    $item->stuff_status = $product_info->fields['products_status'];
    $item->post_free = (boolean) $product_info->fields['product_is_always_free_shipping'];
    $item->is_discount = $product_info->fields['products_discount_type'];
    $item->currency = $_SESSION['currency'];
    $item->is_free = $product_info->fields['product_is_free'];

    $currencies = new currencies();

    if ($getDetial) {
        $item->skus = get_item_skus($item->item_id);
    }
    if ($getDetial) {
        // $item->price_list = get_item_discount_quantity($item->item_id);
    }

    $itemPrices = kancart_get_products_display_price($product_info);

    $item->prices['base_price'] = $itemPrices['base_price'];



    if ($product_info->fields['products_priced_by_attribute'] == '1') {
        $item->prices['base_price']['price'] = $product_info->fields['products_price'];
    }

    if ($product_info->fields['product_is_free'] == '1') {
        $item->prices['base_price']['price'] = 0;
    }


    $item->prices['currency'] = $_SESSION['currency'];
    $item->discount = $itemPrices['discount'];

    // display prices
    $displayPrices = $itemPrices['prices'];

    $item->prices['display_prices'] = $displayPrices;

    $item->prices['tier_prices'] = $itemPrices['tier_prices'];

    // build show flags from product type layout settings
//    $flag_show_product_info_starting_at = zen_get_show_product_switch($item->item_id, 'starting_at');
//    $flag_show_product_info_model = zen_get_show_product_switch($item->item_id, 'model');
//    $flag_show_product_info_weight = zen_get_show_product_switch($item->item_id, 'weight');
//    $flag_show_product_info_quantity = zen_get_show_product_switch($item->item_id, 'quantity');
//    $flag_show_product_info_manufacturer = zen_get_show_product_switch($item->item_id, 'manufacturer');
//    $flag_show_product_info_in_cart_qty = zen_get_show_product_switch($item->item_id, 'in_cart_qty');
//    $flag_show_product_info_tell_a_friend = zen_get_show_product_switch($item->item_id, 'tell_a_friend');
//    $flag_show_product_info_reviews = zen_get_show_product_switch($item->item_id, 'reviews');
//    $flag_show_product_info_reviews_count = zen_get_show_product_switch($item->item_id, 'reviews_count');
//    $flag_show_product_info_date_available = zen_get_show_product_switch($item->item_id, 'date_available');
//    $flag_show_product_info_date_added = zen_get_show_product_switch($item->item_id, 'date_added');
//    $flag_show_product_info_url = zen_get_show_product_switch($item->item_id, 'url');
//    $flag_show_product_info_additional_images = zen_get_show_product_switch($item->item_id, 'additional_images');
//    $flag_show_product_info_free_shipping = zen_get_show_product_switch($item->item_id, 'always_free_shipping_image_switch');

    KCLogger::Log('product_is_free: ' . $product_info->fields['product_is_free']);
    KCLogger::Log('products_priced_by_attribute: ' . $product_info->fields['products_priced_by_attribute']);

    $display_normal_price = zen_get_products_base_price($item->item_id);
    $display_special_price = zen_get_products_special_price($item->item_id, true);
    $display_sale_price = zen_get_products_special_price($item->item_id, false);
    $currentCurrenyValue = $currencies->currencies[$_SESSION['currency']]['value'];

    KCLogger::Log('$display_normal_price: ' . $display_normal_price);
    KCLogger::Log('$display_price: ' . $display_special_price);
    KCLogger::Log('$display_sale_price: ' . $display_sale_price);
    KCLogger::Log('currency value: ' . $currentCurrenyValue);

    $productTaxRate = zen_get_tax_rate($product_info->fields['products_tax_class_id']);

    if ($display_sale_price) {
        $item->price = $currencies->value(zen_add_tax($display_sale_price, $productTaxRate), false) * $currentCurrenyValue;
    } else if ($display_special_price) {
        $item->price = $currencies->value(zen_add_tax($display_special_price, $productTaxRate), false) * $currentCurrenyValue;
    } else if ($display_normal_price) {
        $item->price = $currencies->value(zen_add_tax($display_normal_price, $productTaxRate), false) * $currentCurrenyValue;
    }

    $item->original_price = $currencies->value(zen_add_tax($display_normal_price, $productTaxRate), false) * $currentCurrenyValue;


    //	$manufacturers_name = zen_get_products_manufacturers_name($item_id);//functions/general.php
    //	if(strlen($manufacturers_name)>0){
    //		$item->is_recommended = '1';
    //	}
    //	else{
    //		$item->is_recommended = '0';
    //	}
    //	if ($new_price = zen_get_products_special_price($item_id)){//functions/functions_prices
    //		$item->is_specials = '1';
    //	}
    //	else{
    //		$item->is_specials = '0';
    //	}

    $item->rating_count = kancart_item_reviews_count($item->item_id);
    $item->rating_score = kancart_item_reviews_score($item->item_id);
    $item->rating_score = $item->rating_score ? $item->rating_score : 0;
    return $item;
}

/**
 * 获取商品主图
 * @param type $product
 * @param string $type base/medium/large
 * @return string 
 */
function kancart_get_item_main_image($product, $type = 'base') {
    KCLogger::Log('item_image: 1');
    $products_image = $product->fields['products_image'];
    KCLogger::Log('item_image: 2 ' . $products_image);

    $products_image_extension = substr($products_image, strrpos($products_image, '.'));
    $products_image_base = str_replace($products_image_extension, '', $products_image);
    $products_image_medium = $products_image_base . IMAGE_SUFFIX_MEDIUM . $products_image_extension;
    $products_image_large = $products_image_base . IMAGE_SUFFIX_LARGE . $products_image_extension;
    KCLogger::Log('item_image: 3 ' . $products_image_base);
    switch ($type) {
        case 'medium': {
                // check for a medium image else use small
                if (!file_exists(DIR_WS_IMAGES . 'medium/' . $products_image_medium)) {
                    $products_image_medium = DIR_WS_IMAGES . $products_image;
                } else {
                    $products_image_medium = DIR_WS_IMAGES . 'medium/' . $products_image_medium;
                }
                KCLogger::Log('item_image: 4 ' . $products_image_medium);
                return HTTP_SERVER . DIR_WS_CATALOG . $products_image_medium;
            } break;
        case 'large': {
                // check for a large image else use medium else use small
                if (!file_exists(DIR_WS_IMAGES . 'large/' . $products_image_large)) {
                    if (!file_exists(DIR_WS_IMAGES . 'medium/' . $products_image_medium)) {
                        $products_image_large = DIR_WS_IMAGES . $products_image;
                    } else {
                        $products_image_large = DIR_WS_IMAGES . 'medium/' . $products_image_medium;
                    }
                } else {
                    $products_image_large = DIR_WS_IMAGES . 'large/' . $products_image_large;
                }
                KCLogger::Log('item_image: 5 ' . $products_image_large);
                return HTTP_SERVER . DIR_WS_CATALOG . $products_image_large;
            } break;
        default : {
                KCLogger::Log('item_image: 6 ' . $products_image);
                return HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $products_image;
            } break;
    }
}

/**
 * 获取商品附加图片
 * @param type $product
 * @param Item $item
 * @return KC_Itemimg[] 
 */
function kancart_get_item_images($product, $item) {
    $products_image = $product->fields['products_image'];
    $products_id = $product->fields['products_id'];
    $item->item_imgs = array();

    // 判断是否是 通常的 Zencart-LITB 模板方式, 采用 s/ l/ v/ 三种文件夹存放图片的方式
    $isSVLMode = false;
    if (is_int(strpos($products_image, ','))) {
        $isSVLMode = true;
    } else {
        $tmp_products_image = $products_image;

        $single_image_extension = substr($tmp_products_image, strrpos($tmp_products_image, '.'));
        if (substr($tmp_products_image, 0, 1) == '/') {
            $tmp_products_image = substr($tmp_products_image, 1);
        }
        $vImage = DIR_WS_IMAGES . substr_replace(substr($tmp_products_image, 0, strrpos($tmp_products_image, '.')), 'v/', 0, 2) . $single_image_extension;
        $lImage = DIR_WS_IMAGES . substr_replace(substr($tmp_products_image, 0, strrpos($tmp_products_image, '.')), 'l/', 0, 2) . $single_image_extension;
        if (file_exists($lImage) || file_exists($vImage)) {
            $isSVLMode = true;
        }
    }

    if ($isSVLMode) {
        // 通常的 Zencart-LITB 模板方式
        // 字段存储着多张图片的信息
        $imgsrcs = split(',', $products_image);
        $imgsrcs_image_extension = array();
        for ($i = 0; $i < count($imgsrcs); $i++) {
            if ($i == 0) {
                $item->thumbnail_pic_url = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $imgsrcs[$i];
                KCLogger::Log('$item->thumbnail_pic_url 1: ' . $item->thumbnail_pic_url);
            }
            $itemImg = new KC_Itemimg();
            $itemImg->img_id = $item->item_id . ($i + 1);
            $itemImg->position = $i + 1;
            $imgsrcs_image_extension[$i] = substr($imgsrcs[$i], strrpos($imgsrcs[$i], '.'));
            if (substr($imgsrcs[$i], 0, 1) == '/') {
                $imgsrcs[$i] = substr($imgsrcs[$i], 1);
            }

            $itemImg->img_url = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . substr_replace(substr($imgsrcs[$i], 0, strrpos($imgsrcs[$i], '.')), 'v/', 0, 2) . $imgsrcs_image_extension[$i];
            if ($i == 0) {
                $item->main_pic_url = $itemImg->img_url;
                KCLogger::Log('$item->main_pic_url 1: ' . $item->main_pic_url);
            }
            $item->item_imgs[] = $itemImg;
            //$imgsrcs_s[$i] = HTTP_SERVER . DIR_WS_IMAGES . substr_replace(substr($imgsrcs[$i], 0, strrpos($imgsrcs[$i], '.')), 's/', 0, 2) . $imgsrcs_image_extension[$i];
            //$imgsrcs_v[$i] = HTTP_SERVER . DIR_WS_IMAGES . substr_replace(substr($imgsrcs[$i], 0, strrpos($imgsrcs[$i], '.')), 'v/', 0, 2) . $imgsrcs_image_extension[$i];
            //$imgsrcs_l[$i] = HTTP_SERVER . DIR_WS_IMAGES . substr_replace(substr($imgsrcs[$i], 0, strrpos($imgsrcs[$i], '.')), 'l/', 0, 2) . $imgsrcs_image_extension[$i];
        }
    } else {
        // zencart 原生方式
        // 自动检测格式为 xxxxxx_0.jpg  xxxxxx_1.jpg.....的图片
        $images_array = array();
        if ($products_image != '') {
            // prepare image name
            $products_image_extension = substr($products_image, strrpos($products_image, '.'));
            $products_image_base = str_replace($products_image_extension, '', $products_image);

            // if in a subdirectory
            if (strrpos($products_image, '/')) {
                $products_image_match = substr($products_image, strrpos($products_image, '/') + 1);
                //echo 'TEST 1: I match ' . $products_image_match . ' - ' . $file . ' -  base ' . $products_image_base . '<br>';
                $products_image_match = str_replace($products_image_extension, '', $products_image_match) . '_';
                $products_image_base = $products_image_match;
            }
            $products_image_directory = str_replace($products_image, '', substr($products_image, strrpos($products_image, '/')));
            if ($products_image_directory != '') {
                $products_image_directory = DIR_WS_IMAGES . str_replace($products_image_directory, '', $products_image) . "/";
            } else {
                $products_image_directory = DIR_WS_IMAGES;
            }
            // Check for additional matching images
            $file_extension = $products_image_extension;
            $products_image_match_array = array();
            $dir = @dir($products_image_directory);
            if ($dir) {
                while ($file = $dir->read()) {
                    if (!is_dir($products_image_directory . $file)) {
                        if (substr($file, strrpos($file, '.')) == $file_extension) {
                            //          if(preg_match("/" . $products_image_match . "/i", $file) == '1') {
                            if (preg_match("/" . $products_image_base . "/i", $file) == 1) {
                                if ($file != $products_image) {
                                    if ($products_image_base . str_replace($products_image_base, '', $file) == $file) {
                                        //echo 'I AM A MATCH ' . $file . '<br>';
                                        $images_array[] = $file;
                                    } else {
                                        //echo 'I AM NOT A MATCH ' . $file . '<br>';
                                    }
                                }
                            }
                        }
                    }
                }
                if (sizeof($images_array)) {
                    sort($images_array);
                }
                $dir->close();
            }
        }
        $i = 0;
        foreach ($images_array as $key => $value) {
            $itemImg = new KC_Itemimg();
            $itemImg->img_id = $products_id . "_" . ($i + 1);
            $itemImg->position = $i;
            $products_image_large = str_replace(DIR_WS_IMAGES, DIR_WS_IMAGES . 'large/', $products_image_directory) . str_replace($products_image_extension, '', $value) . IMAGE_SUFFIX_LARGE . $products_image_extension;
            $flag_has_large = file_exists($products_image_large);
            $products_image_large = ($flag_has_large ? $products_image_large : $products_image_directory . $value);
            $itemImg->img_url = HTTP_SERVER . DIR_WS_CATALOG . $products_image_large;
            $item->item_imgs[] = $itemImg;
            $i++;
        }
    }
    KCLogger::Log('$itemImages: ' . json_encode($item->item_imgs));
}

/**
 * 获取商品Skus
 * @param string $item_id
 * @return array
 */
function get_item_skus($item_id) {
    Global $db;
    $skus = array();
    // 查询sku的数量
    // limit to 1 for performance when processing larger tables
    $sql = "select count(*) as total
            from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib
            where    patrib.products_id='" . (int) $item_id . "'
            and      patrib.options_id = popt.products_options_id
            and      popt.language_id = '" . (int) $_SESSION['languages_id'] . "'" .
            " limit 1";

    $pr_attr = $db->Execute($sql);
    // 如果有sku 则进行详细查询
    if ($pr_attr->fields['total'] > 0) {
        if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
            $options_order_by = ' order by LPAD(popt.products_options_sort_order,11,"0")';
        } else {
            $options_order_by = ' order by popt.products_options_name';
        }

        $sql = "select distinct popt.products_options_id, popt.products_options_name, popt.products_options_sort_order,
                              popt.products_options_type, popt.products_options_length, popt.products_options_comment,
                              popt.products_options_size,
                              popt.products_options_images_per_row,
                              popt.products_options_images_style,
                              popt.products_options_rows,
                              patrib.attributes_required
              from        " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib
              where           patrib.products_id='" . (int) $item_id . "'
              and             patrib.options_id = popt.products_options_id
              and             popt.language_id = '" . (int) $_SESSION['languages_id'] . "' " .
                $options_order_by;

        $products_options_names = $db->Execute($sql);

        if (PRODUCTS_OPTIONS_SORT_BY_PRICE == '1') {
            $order_by = ' order by LPAD(pa.products_options_sort_order,11,"0"), pov.products_options_values_name';
        } else {
            $order_by = ' order by LPAD(pa.products_options_sort_order,11,"0"), pa.options_values_price';
        }

        $discount_type = zen_get_products_sale_discount_type((int) $item_id);
        $discount_amount = zen_get_discount_calc((int) $item_id);

        $zv_display_select_option = 0;

        $currencies = new currencies();
        $currencyvalue = $currencies->get_value($_SESSION['currency']);

        while (!$products_options_names->EOF) {
            $option_fields = $products_options_names->fields;
            /*
              class Sku
              {
              var $Sku_id= '';
              var $name= '';
              var $mode= '';
              var $allow_blank= '';
              var $items= array();
              var $parent_id= '';
              var $custom_name= '';
              var $custom_price= '';
              var $custom_text= '';
              var $child= array();
              function Sku()
              {}
              }
             */

            $sku = new KC_Sku();
            $sku->sku_id = $products_options_names->fields['products_options_id'];
            $sku->name = $products_options_names->fields['products_options_name'];
            // PRODUCTS_OPTIONS_TYPE_RADIO
            // PRODUCTS_OPTIONS_TYPE_TEXT
            // PRODUCTS_OPTIONS_TYPE_CHECKBOX
            // PRODUCTS_OPTIONS_TYPE_READONLY
            $sku->mode = $products_options_names->fields['products_options_type'];
            if ($sku->mode == PRODUCTS_OPTIONS_TYPE_RADIO || $sku->mode == 0) {
                $sku->mode = 'select';
            } else if ($sku->mode == PRODUCTS_OPTIONS_TYPE_CHECKBOX) {
                $sku->mode = 'multiple_select';
            } else if ($sku->mode == PRODUCTS_OPTIONS_TYPE_TEXT) {
                $sku->mode = 'input';
            } else if ($sku->mode == PRODUCTS_OPTIONS_TYPE_READONLY) {
                $sku->mode = 'readonly';
            }

            $sku->parent_id = '';

            $sku->custom_name = '';
            $sku->custom_price = 0;
            $sku->custom_text = '';
            $sku->allow_blank = $products_options_names->fields['attributes_required'];
            $sku->child = array();

            $sku->values = array();

            $products_options_array = array();

            /*
              pa.options_values_price, pa.price_prefix,
              pa.products_options_sort_order, pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
              pa.attributes_default, pa.attributes_discounted, pa.attributes_image
             */

            $sql = "select    pov.products_options_values_id,
                        pov.products_options_values_name,
                        pa.*
              from      " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
              where     pa.products_id = '" . (int) $item_id . "'
              and       pa.options_id = '" . (int) $products_options_names->fields['products_options_id'] . "'
              and       pa.options_values_id = pov.products_options_values_id
              and       pov.language_id = '" . (int) $_SESSION['languages_id'] . "' " .
                    $order_by;

            $products_options = $db->Execute($sql);
            $defaultSkuValue = null;
            while (!$products_options->EOF) {

                $fields = $products_options->fields;
                $display_only = $products_options->fields['attributes_display_only'];

                if ($display_only == '0') {
                    /*
                      attributes_required

                      class SkuValue
                      {
                      var $value_id= '';
                      var $sku_id = '';
                      var $name= '';
                      var $price= '';
                      var $qty= '';
                      function SkuValue()
                      {}
                      }
                     */
                    $sku_value = new KC_SkuValue();
                    $sku_value->sku_id = $sku->sku_id;
                    // TODO 判断是使用 values_id 还是  attributes_id
                    // $sku_value->value_id = $products_options->fields['products_options_attributes_id'];
                    $sku_value->value_id = $products_options->fields['products_options_values_id'];
                    $sku_value->name = $products_options->fields['products_options_values_name'];
                    $is_default = $products_options->fields['attributes_default'] == '1' ? true : false;
                    if ($is_default) {
                        $defaultSkuValue = $sku_value;
                    }

                    $price_prefix = $products_options->fields['price_prefix'];
                    if ($price_prefix != '-') {
                        $price_prefix = 1;
                    } else {
                        $price_prefix = -1;
                    }

                    $sku_value->price = $currencies->value($products_options->fields['options_values_price'] * $price_prefix, false) * $currencies->currencies[$_SESSION['currency']]['value'];

                    $sku->allow_blank = $products_options->fields['attributes_required'] == '1' ? true : false;

                    // collect price information if it exists
                    // 属性打折
                    if ($products_options->fields['attributes_discounted'] == '1') {
                        // 该商品属性打折
                        // apply product discount to attributes if discount is on
                        //              $new_attributes_price = $products_options->fields['options_values_price'];
                        $new_attributes_price = zen_get_attributes_price_final($products_options->fields["products_attributes_id"], 1, '', 'false');
                        KCLogger::Log('$new_attributes_price: ' . $new_attributes_price);
                        $new_attributes_price = zen_get_discount_calc((int) $item_id, true, $new_attributes_price);
                    } else {
                        // 该商品属性不打折
                        // discount is off do not apply
                        $new_attributes_price = $products_options->fields['options_values_price'];
                    }


                    // 将负数转成正数
                    // reverse negative values for display
                    if ($new_attributes_price < 0) {
                        $new_attributes_price = -$new_attributes_price;
                    }
                    $sku_value->price = $currencies->value(zen_add_tax($new_attributes_price, zen_get_tax_rate($product_info->fields['products_tax_class_id'])) * $price_prefix, false) * $currencies->currencies[$_SESSION['currency']]['value'];
                    if ($products_options->fields['product_attribute_is_free'] == '1') {
                        $sku_value->price = 0;
                    }

                    $sku->values[] = $sku_value;
                }
                $products_options->MoveNext();
            }
            if ($defaultSkuValue) {
                $defaultSkuValue->is_default = true;
            }
            $skus[] = $sku;
            $products_options_names->MoveNext();
        }
    }
    return $skus;
}

function get_item_discount_quantity($product) {
    Global $db, $currencies;
    $currentCurrenyValue = $currencies->currencies[$_SESSION['currency']]['value'];

    $products_id = $product->fields['products_id'];
    KCLogger::Log('item_discount_quantity: 1');
    KCLogger::Log('$product->fields: ' . json_encode($product->fields));
    $products_discount_type_from = $product->fields['products_discount_type_from'];
    $products_discount_type = $product->fields['products_discount_type'];


    KCLogger::Log('item_discount_quantity: 2 ');
    KCLogger::Log('$products_discount_type_from: ' . (string) $products_discount_type_from);
    KCLogger::Log('$products_discount_type: ' . (string) $products_discount_type);
    // 如果用户授权是打开的， 那么不显示折扣信息
    switch (true) {
        case (CUSTOMERS_APPROVAL == '1' and $_SESSION['customer_id'] == ''):
            // 用户必须登录才能看到折扣 返回空数组
            KCLogger::Log('item_discount_quantity: 3');
            return array();
            break;
        case (STORE_STATUS == 1 || CUSTOMERS_APPROVAL == '2' and $_SESSION['customer_id'] == ''):
            // 用户可以查看折扣价格，但是没有折扣价格信息， 返回空数组
            KCLogger::Log('item_discount_quantity: 4');
            return array();
            break;
        case (CUSTOMERS_APPROVAL == '3' and TEXT_LOGIN_FOR_PRICE_PRICE_SHOWROOM != ''):
            // 用户可以查看折扣价格，但是没有折扣价格信息， 返回空数组
            KCLogger::Log('item_discount_quantity: 5');
            return array();
            break;
        case (CUSTOMERS_APPROVAL_AUTHORIZATION != '0' and $_SESSION['customer_id'] == ''):
            // 用户必须登录才能看到折扣 返回空数组
            KCLogger::Log('item_discount_quantity: 6');
            return array();
            break;
        case ((CUSTOMERS_APPROVAL_AUTHORIZATION != '0' and CUSTOMERS_APPROVAL_AUTHORIZATION != '3') and $_SESSION['customers_authorization'] > '0'):
            // 用户必须登录才能看到折扣 返回空数组
            KCLogger::Log('item_discount_quantity: 7');
            return array();
            break;
        default:
            // 用户可以查看折扣价格信息， 正常处理
            break;
    }
    KCLogger::Log('item_discount_quantity: 8');
    // 创建商品折扣输出表
    // 获取这个商品的最小购买数量
    $products_min_query = $db->Execute("select products_quantity_order_min from " . TABLE_PRODUCTS . " where products_id='" . (int) $products_id . "'");
    $products_quantity_order_min = $products_min_query->fields['products_quantity_order_min'];
    KCLogger::Log('item_discount_quantity: 9');
    // 获取这个商品的折扣价格列表
    $products_discounts_query = $db->Execute("select * from " . TABLE_PRODUCTS_DISCOUNT_QUANTITY . " where products_id='" . (int) $products_id . "' and discount_qty !=0 " . " order by discount_qty");
    KCLogger::Log('item_discount_quantity: 10');
    // 每行显示多少个价格优惠，没有用
    // $discount_col_cnt = DISCOUNT_QUANTITY_PRICES_COLUMN; 
    // 获取商品的原价
    $display_price = zen_get_products_base_price($products_id);
    // 获取商品的特价
    $display_specials_price = zen_get_products_special_price($products_id, true);

    // 设置第一个价格
    if ($display_specials_price == false) {
        // 没有特价，显示商品原价
        $show_price = $display_price;
    } else {
        // 有特价， 显示商品特价
        $show_price = $display_specials_price;
    }

    switch (true) {
        case ($products_discounts_query->fields['discount_qty'] <= 2):
            // 如果折扣的数量小于等于2 那么设置商品数量为1
            $show_qty = '1';
            break;
        case ($products_quantity_order_min == ($products_discounts_query->fields['discount_qty'] - 1) || $products_quantity_order_min == ($products_discounts_query->fields['discount_qty'])):
            // 如果商品最小购买数量等于或者比折扣数量小1， 那么就显示最小购买数量
            $show_qty = $products_quantity_order_min;
            break;
        default:
            // 否则显示 最小购买数量到折扣数量-1
            $show_qty = $products_quantity_order_min . '-' . number_format($products_discounts_query->fields['discount_qty'] - 1);
            break;
    }

    //$currencies->display_price($discounted_price, zen_get_tax_rate(1), 1)

    $display_price = zen_get_products_base_price($products_id);
    $display_specials_price = zen_get_products_special_price($products_id, true);
    KCLogger::Log('item_discount_quantity: 11');

    $quantityDiscounts = array();
    /*
     * min_qty
     * max_qty
     * price
     * type NONE PERCENTAGE ACTUAL OFFPRICE
     *
     */
    while (!$products_discounts_query->EOF) {
        KCLogger::Log('item_discount_quantity-while: 1');
        $qtyDiscount = array();
        switch ($products_discount_type) {
            // none
            case '0':
                KCLogger::Log('item_discount_quantity-while: 2');
                $qtyDiscount['price'] = 0;
                $qtyDiscount['type'] = 'none';
                break;
            // percentage discount
            case '1':
                KCLogger::Log('item_discount_quantity-while: 3');
                $qtyDiscount['type'] = 'percentage';
                if ($products_discount_type_from == '0') {
                    $qtyDiscount['price'] = $display_price - ($display_price * ($products_discounts_query->fields['discount_price'] / 100));
                } else {
                    if (!$display_specials_price) {
                        $qtyDiscount['price'] = $display_price - ($display_price * ($products_discounts_query->fields['discount_price'] / 100));
                    } else {
                        $qtyDiscount['price'] = $display_specials_price - ($display_specials_price * ($products_discounts_query->fields['discount_price'] / 100));
                    }
                }
                break;
            // actual price
            case '2':
                KCLogger::Log('item_discount_quantity-while: 4');
                $qtyDiscount['type'] = 'actual';
                if ($products_discount_type_from == '0') {
                    $qtyDiscount['price'] = $products_discounts_query->fields['discount_price'];
                } else {
                    $qtyDiscount['price'] = $products_discounts_query->fields['discount_price'];
                }
                break;
            // amount offprice
            case '3':
                KCLogger::Log('item_discount_quantity-while: 5');
                $qtyDiscount['type'] = 'offprice';
                if ($products_discount_type_from == '0') {
                    $qtyDiscount['price'] = $display_price - $products_discounts_query->fields['discount_price'];
                } else {
                    if (!$display_specials_price) {
                        $qtyDiscount['price'] = $display_price - $products_discounts_query->fields['discount_price'];
                    } else {
                        $qtyDiscount['price'] = $display_specials_price - $products_discounts_query->fields['discount_price'];
                    }
                }
                break;
        }
        KCLogger::Log('item_discount_quantity-while: 6');
        $qtyDiscount['min_qty'] = number_format($products_discounts_query->fields['discount_qty']);
        $products_discounts_query->MoveNext();
        if ($products_discounts_query->EOF) {
            $qtyDiscount['max_qty'] = -1;
        } else {
            if (($products_discounts_query->fields['discount_qty'] - 1) != $show_qty) {
                if ($qtyDiscount['min_qty'] < $products_discounts_query->fields['discount_qty'] - 1) {
                    // 设置最大数
                    $qtyDiscount['max_qty'] = number_format($products_discounts_query->fields['discount_qty'] - 1);
                }
            }
        }
        KCLogger::Log('$qtyDiscount: ' . json_encode($qtyDiscount));
        KCLogger::Log('item_discount_quantity-while: 7');
        KCLogger::Log('$currencies->value($qtyDiscount[\'price\'], false)' . $currencies->value($qtyDiscount['price'], false));
        KCLogger::Log('$currencies->currencies[$_SESSION[\'currency\']][\'value\']' . $currencies->currencies[$_SESSION['currency']]['value']);

        $qtyDiscount['price'] = $currencies->value($qtyDiscount['price'], false) * $currencies->currencies[$_SESSION['currency']]['value'];

        KCLogger::Log('item_discount_quantity-while: 10');
        KCLogger::Log('item_discount_quantity-while: ' . json_encode($qtyDiscount));
        $quantityDiscounts[] = $qtyDiscount;
    }
    KCLogger::Log('item_discount_quantity: 12');
    $currentMaxQty = -1;
    for ($i = count($quantityDiscounts) - 1; $i >= 0; $i--) {
        $qtyDiscount = $quantityDiscounts[$i];
        if (!isset($qtyDiscount['max_qty'])) {
            $qtyDiscount['max_qty'] = $currentMaxQty;
        }
        $currentMaxQty = $quantityDiscounts[$i]['min_qty'] - 1;
    }
    return $quantityDiscounts;
}

/**
 * 查询商品
 * @return multitype:
 */
function kancart_items_get() {
    Global $kcResponse, $db;
    $define_list = array('PRODUCT_LIST_MODEL' => PRODUCT_LIST_MODEL,
        'PRODUCT_LIST_NAME' => PRODUCT_LIST_NAME,
        'PRODUCT_LIST_MANUFACTURER' => PRODUCT_LIST_MANUFACTURER,
        'PRODUCT_LIST_PRICE' => PRODUCT_LIST_PRICE,
        'PRODUCT_LIST_QUANTITY' => PRODUCT_LIST_QUANTITY,
        'PRODUCT_LIST_WEIGHT' => PRODUCT_LIST_WEIGHT,
        'PRODUCT_LIST_IMAGE' => PRODUCT_LIST_IMAGE);

    asort($define_list);

    $column_list = array();
    reset($define_list);
    while (list($column, $value) = each($define_list)) {
        if ($value)
            $column_list[] = $column;
    }

    $select_column_list = '';

    for ($col = 0, $n = sizeof($column_list); $col < $n; $col++) {
        if (($column_list[$col] == 'PRODUCT_LIST_NAME') || ($column_list[$col] == 'PRODUCT_LIST_PRICE')) {
            continue;
        }

        if (zen_not_null($select_column_list)) {
            $select_column_list .= ', ';
        }

        switch ($column_list[$col]) {
            case 'PRODUCT_LIST_MODEL':
                $select_column_list .= 'p.products_model';
                break;
            case 'PRODUCT_LIST_MANUFACTURER':
                $select_column_list .= 'm.manufacturers_name';
                break;
            case 'PRODUCT_LIST_QUANTITY':
                $select_column_list .= 'p.products_quantity';
                break;
            case 'PRODUCT_LIST_IMAGE':
                $select_column_list .= 'p.products_image';
                break;
            case 'PRODUCT_LIST_WEIGHT':
                $select_column_list .= 'p.products_weight';
                break;
        }
    }
    /*
      // always add quantity regardless of whether or not it is in the listing for add to cart buttons
      if (PRODUCT_LIST_QUANTITY < 1) {
      $select_column_list .= ', p.products_quantity ';
      }
     */

    // always add quantity regardless of whether or not it is in the listing for add to cart buttons
    if (PRODUCT_LIST_QUANTITY < 1) {
        if (empty($select_column_list)) {
            $select_column_list .= ' p.products_quantity ';
        } else {
            $select_column_list .= ', p.products_quantity ';
        }
    }

    if (zen_not_null($select_column_list)) {
        $select_column_list .= ', ';
    }

    //  $select_str = "select distinct " . $select_column_list . " m.manufacturers_id, p.products_id, pd.products_name, p.products_price, p.products_tax_class_id, IF(s.status = 1, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status = 1, s.specials_new_products_price, p.products_price) as final_price ";
    $select_str = "SELECT DISTINCT " . $select_column_list .
            " m.manufacturers_id, p.products_id, pd.products_name, p.products_price, p.products_tax_class_id, p.products_price_sorter, p.products_qty_box_status, p.master_categories_id ,p.product_is_always_free_shipping ";

    if ((DISPLAY_PRICE_WITH_TAX == 'true') && ((isset($_POST['start_price']) && zen_not_null($_POST['start_price'])) || (isset($_POST['end_price']) && zen_not_null($_POST['end_price'])))) {
        $select_str .= ", SUM(tr.tax_rate) AS tax_rate ";
    }

    //  $from_str = "from " . TABLE_PRODUCTS . " p left join " . TABLE_MANUFACTURERS . " m using(manufacturers_id), " . TABLE_PRODUCTS_DESCRIPTION . " pd left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id, " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c";
    $from_str = "FROM (" . TABLE_PRODUCTS . " p
             LEFT JOIN " . TABLE_MANUFACTURERS . " m
             USING(manufacturers_id), " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c )
             LEFT JOIN " . TABLE_META_TAGS_PRODUCTS_DESCRIPTION . " mtpd
             ON mtpd.products_id= p2c.products_id
             AND mtpd.language_id = :languagesID";

    $from_str = $db->bindVars($from_str, ':languagesID', $_SESSION['languages_id'], 'integer');

    // 以后扩展， 用户选择自己的国家和地区之后计算价格
    if ((DISPLAY_PRICE_WITH_TAX == 'true') && ((isset($_POST['start_price']) && zen_not_null($_POST['start_price'])) || (isset($_POST['end_price']) && zen_not_null($_POST['end_price'])))) {
        if (!$_SESSION['customer_country_id']) {
            $_SESSION['customer_country_id'] = STORE_COUNTRY;
            $_SESSION['customer_zone_id'] = STORE_ZONE;
        }
        $from_str .= " LEFT JOIN " . TABLE_TAX_RATES . " tr
                     ON p.products_tax_class_id = tr.tax_class_id
                     LEFT JOIN " . TABLE_ZONES_TO_GEO_ZONES . " gz
                     ON tr.tax_zone_id = gz.geo_zone_id
                     AND (gz.zone_country_id IS null OR gz.zone_country_id = 0 OR gz.zone_country_id = :zoneCountryID)
                     AND (gz.zone_id IS null OR gz.zone_id = 0 OR gz.zone_id = :zoneID)";

        $from_str = $db->bindVars($from_str, ':zoneCountryID', $_SESSION['customer_country_id'], 'integer');
        $from_str = $db->bindVars($from_str, ':zoneID', $_SESSION['customer_zone_id'], 'integer');
    }

    $where_str = " WHERE (p.products_status = 1
               AND p.products_id = pd.products_id
               AND pd.language_id = :languagesID
               AND p.products_id = p2c.products_id
               AND p2c.categories_id = c.categories_id
               AND p.product_is_call = 0 ";
    // 不支持CALL FOR PRICE 的商品

    $where_str = $db->bindVars($where_str, ':languagesID', $_SESSION['languages_id'], 'integer');

    // reset previous selection
    // 是否包含子目录
    if (!isset($_POST['include_subcat'])) {
        $_POST['is_include_subcat'] = '0';
    }
    $_POST['include_subcat'] = (int) $_POST['include_subcat'];

    // 是否在描述中搜索
    if (!isset($_POST['search_description'])) {
        $_POST['search_description'] = '0';
    }

    $_POST['search_description'] = (int) $_POST['search_description'];

    $post_free = false;
    // 只搜索免运费商品
    if (isset($_POST['post_free'])) {
        $post_free = (boolean) $_POST['post_free'];
    }

    $searchCid = false;
    if (isset($_POST['cid']) && zen_not_null($_POST['cid'])) {
        $searchCid = $_POST['cid'];
    } else if (isset($_POST['special']) && zen_not_null($_POST['special'])) {
        $searchCid = $_POST['special'];
    }
    if ($searchCid == -1) {
        $searchCid = 0;
    }
    if ($searchCid) {
        if ($searchCid == "FEATURED" || $searchCid == "SPECIALS") {
            $ex_querry = '';
            $ex_id = '';
            $ex_result = '';
            if ($searchCid == "FEATURED")
                $ex_querry = "select products_id from featured where status = 1";
            else
                $ex_querry = "select products_id from specials where status = 1";
            $ex_id = $db->Execute($ex_querry);
            while (!$ex_id->EOF) {
                $ex_result .= $ex_id->fields['products_id'];
                $ex_result .= ',';
                $ex_id->MoveNext();
            }
            $ex_result = substr($ex_result, 0, -1);
            if (strlen($ex_result) > 0) {
                $where_str .=" AND p.products_id in (" . $ex_result . ") ";
            }
        }
        if ($_POST['include_subcat'] == '1') {
            $subcategories_array = array();
            zen_get_subcategories($subcategories_array, $searchCid);
            $where_str .= " AND p2c.products_id = p.products_id
                    AND p2c.products_id = pd.products_id
                    AND (p2c.categories_id = :categoriesID";

            $where_str = $db->bindVars($where_str, ':categoriesID', $searchCid, 'integer');

            if (sizeof($subcategories_array) > 0) {
                $where_str .= " OR p2c.categories_id in (";
                for ($i = 0, $n = sizeof($subcategories_array); $i < $n; $i++) {
                    $where_str .= " :categoriesID";
                    if ($i + 1 < $n)
                        $where_str .= ",";
                    $where_str = $db->bindVars($where_str, ':categoriesID', $subcategories_array[$i], 'integer');
                }
                $where_str .= ")";
            }
            $where_str .= ")";
        } else {
            $where_str .= " AND p2c.products_id = p.products_id
                    AND p2c.products_id = pd.products_id
                    AND pd.language_id = :languagesID
                    AND p2c.categories_id = :categoriesID";

            $where_str = $db->bindVars($where_str, ':categoriesID', $searchCid, 'integer');
            $where_str = $db->bindVars($where_str, ':languagesID', $_SESSION['languages_id'], 'integer');
        }
    }

    if (isset($_POST['manufacturers_id']) && zen_not_null($_POST['manufacturers_id'])) {
        $where_str .= " AND m.manufacturers_id = :manufacturersID";
        $where_str = $db->bindVars($where_str, ':manufacturersID', $_POST['manufacturers_id'], 'integer');
    }

    // 加入查询关键字条件
    if (isset($_POST['query']) && zen_not_null($_POST['query'])) {
        if (zen_parse_search_string(stripslashes($_POST['query']), $search_keywords)) {
            $where_str .= " AND (";
            for ($i = 0, $n = sizeof($search_keywords); $i < $n; $i++) {
                switch ($search_keywords[$i]) {
                    case '(':
                    case ')':
                    case 'and':
                    case 'or':
                        $where_str .= " " . $search_keywords[$i] . " ";
                        break;
                    default:
                        $where_str .= "(pd.products_name LIKE '%:keywords%'
                                         OR p.products_model
                                         LIKE '%:keywords%'
                                         OR m.manufacturers_name
                                         LIKE '%:keywords%'";

                        $where_str = $db->bindVars($where_str, ':keywords', $search_keywords[$i], 'noquotestring');
                        // search meta tags
                        $where_str .= " OR (mtpd.metatags_keywords
                        LIKE '%:keywords%'
                        AND mtpd.metatags_keywords !='')";

                        $where_str = $db->bindVars($where_str, ':keywords', $search_keywords[$i], 'noquotestring');

                        $where_str .= " OR (mtpd.metatags_description
                        LIKE '%:keywords%'
                        AND mtpd.metatags_description !='')";

                        $where_str = $db->bindVars($where_str, ':keywords', $search_keywords[$i], 'noquotestring');

                        if (isset($_POST['search_in_description']) && ($_POST['search_in_description'] == '1')) {
                            $where_str .= " OR pd.products_description
                          LIKE '%:keywords%'";

                            $where_str = $db->bindVars($where_str, ':keywords', $search_keywords[$i], 'noquotestring');
                        }
                        $where_str .= ')';
                        break;
                }
            }
            $where_str .= " ))";
        }
    }
    if (!isset($_POST['query']) || $_POST['query'] == "") {
        $where_str .= ')';
    }

    if ($post_free) {
        $where_str .= " AND p.product_is_always_free_shipping = 1 ";
    }


    //die('I SEE ' . $where_str);
    // 以后扩展， 商品的上架日期范围
    //	if (isset($_POST['dfrom']) && zen_not_null($_POST['dfrom']) && ($_POST['dfrom'] != DOB_FORMAT_STRING)) {
    //		$where_str .= " AND p.products_date_added >= :dateAdded";
    //		$where_str = $db->bindVars($where_str, ':dateAdded', zen_date_raw($dfrom), 'date');
    //	}
    //
	//	if (isset($_POST['dto']) && zen_not_null($_POST['dto']) && ($_POST['dto'] != DOB_FORMAT_STRING)) {
    //		$where_str .= " and p.products_date_added <= :dateAdded";
    //		$where_str = $db->bindVars($where_str, ':dateAdded', zen_date_raw($dto), 'date');
    //	}

    $currencies = new currencies();
    // 根据价格进行筛选
    if ($_SESSION['currency'] != DEFAULT_CURRENCY) {
        $rate = $currencies->get_value($_SESSION['currency']);
        if ($rate) {
            $start_price = $_POST['start_price'] / $rate;
            $end_price = $_POST['end_price'] / $rate;
        }
    }

    if (DISPLAY_PRICE_WITH_TAX == 'true') {
        if ($start_price) {
            $where_str .= " AND (p.products_price_sorter * IF(gz.geo_zone_id IS null, 1, 1 + (tr.tax_rate / 100)) >= :price)";
            $where_str = $db->bindVars($where_str, ':price', $start_price, 'float');
        }
        if ($end_price) {
            $where_str .= " AND (p.products_price_sorter * IF(gz.geo_zone_id IS null, 1, 1 + (tr.tax_rate / 100)) <= :price)";
            $where_str = $db->bindVars($where_str, ':price', $end_price, 'float');
        }
    } else {
        if ($start_price) {
            $where_str .= " and (p.products_price_sorter >= :price)";
            $where_str = $db->bindVars($where_str, ':price', $start_price, 'float');
        }
        if ($end_price) {
            $where_str .= " and (p.products_price_sorter <= :price)";
            $where_str = $db->bindVars($where_str, ':price', $end_price, 'float');
        }
    }

    if ((DISPLAY_PRICE_WITH_TAX == 'true') && ((isset($_POST['start_price']) && zen_not_null($_POST['start_price'])) || (isset($_POST['end_price']) && zen_not_null($_POST['end_price'])))) {
        $where_str .= " group by p.products_id, tr.tax_priority";
    }

    // set the default sort order setting from the Admin when not defined by customer
    if (!isset($_POST['order_by'])) {
        $_POST['order_by'] = "bestselling";
    }

    $order_option = explode(":", $_POST['order_by']);

    $order_str = ' order by ';
    if ($order_option[0] == "bestselling") {
        // 按销量排序， 默认降序
        if ($order_option[1] == "asc") {
            $order_str .= "p.products_ordered asc, pd.products_name";
        } else {
            $order_str .= "p.products_ordered desc, pd.products_name";
        }
    } else if ($order_option[0] == "bestmatch") {
        // 按商品名称排序
        if ($order_option[1] == "desc") {
            $order_str .= "pd.products_name desc, p.products_model";
        } else {
            $order_str .= "pd.products_name asc, p.products_model";
        }
    } else if ($order_option[0] == "newarrival") {
        // 新到货品， 按商品添加时间进行排序, 默认降序
        if ($order_option[1] == "asc") {
            $order_str .= "p.products_date_added asc, pd.products_name";
        } else {
            $order_str .= "p.products_date_added desc, pd.products_name";
        }
    } else if ($order_option[0] == "price") {
        // 按价格排序
        if ($order_option[1] == "desc") {
            $order_str .= "p.products_price_sorter desc, pd.products_name";
        } else {
            $order_str .= "p.products_price_sorter asc, pd.products_name";
        }
    } else {
        if ($order_option[1] == "asc") {
            $order_str .= "p.products_ordered asc, pd.products_name";
        } else {
            $order_str .= "p.products_ordered desc, pd.products_name";
        }
    }


    //die('I SEE ' . $_POST['sort'] . ' - ' . PRODUCT_LISTING_DEFAULT_SORT_ORDER);
    // 以后扩展，加入更多的排序选项
    //	if ((!isset($_POST['order_by'])) || (!preg_match('/[1-8][ad]/', $_POST['sort'])) || (substr($_POST['sort'], 0 , 1) > sizeof($column_list))) {
    //		for ($col=0, $n=sizeof($column_list); $col<$n; $col++) {
    //			if ($column_list[$col] == 'PRODUCT_LIST_NAME') {
    //				$_POST['sort'] = $col+1 . 'a';
    //				$order_str = ' order by pd.products_name';
    //				break;
    //			} else {
    //				// sort by products_sort_order when PRODUCT_LISTING_DEFAULT_SORT_ORDER ia left blank
    //				// for reverse, descending order use:
    //				//       $listing_sql .= " order by p.products_sort_order desc, pd.products_name";
    //				$order_str .= " order by p.products_sort_order, pd.products_name";
    //				break;
    //			}
    //		}
    //		// if set to nothing use products_sort_order and PRODUCTS_LIST_NAME is off
    //		if (PRODUCT_LISTING_DEFAULT_SORT_ORDER == '') {
    //			$_POST['sort'] = '20a';
    //		}
    //	} else {
    //		$sort_col = substr($_POST['sort'], 0 , 1);
    //		$sort_order = substr($_POST['sort'], 1);
    //		$order_str = ' order by ';
    //		switch ($column_list[$sort_col-1]) {
    //			case 'PRODUCT_LIST_MODEL':
    //				$order_str .= "p.products_model " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
    //				break;
    //			case 'PRODUCT_LIST_NAME':
    //				$order_str .= "pd.products_name " . ($sort_order == 'd' ? "desc" : "");
    //				break;
    //			case 'PRODUCT_LIST_MANUFACTURER':
    //				$order_str .= "m.manufacturers_name " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
    //				break;
    //			case 'PRODUCT_LIST_QUANTITY':
    //				$order_str .= "p.products_quantity " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
    //				break;
    //			case 'PRODUCT_LIST_IMAGE':
    //				$order_str .= "pd.products_name";
    //				break;
    //			case 'PRODUCT_LIST_WEIGHT':
    //				$order_str .= "p.products_weight " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
    //				break;
    //			case 'PRODUCT_LIST_PRICE':
    //				//        $order_str .= "final_price " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
    //				$order_str .= "p.products_price_sorter " . ($sort_order == 'd' ? "desc" : "") . ", pd.products_name";
    //				break;
    //		}
    //	}
    //$_POST['keyword'] = zen_output_string_protected($_POST['keyword']);
    // 拼装查询语句
    $listing_sql = $select_str . $from_str . $where_str . $order_str;

    $page_size = 20;
    if (isset($_POST['page_size'])) {
        $page_size = (int) $_POST['page_size'];
    }

    // 获取总记录数
    $total_result = new splitPageResults($listing_sql, $page_size, 'p.products_id', 'page');
    //	echo "count :".$result->number_of_rows;
    $page_no = 1;
    if (isset($_POST['page_no'])) {
        $page_no = (int) $_POST['page_no'];
    }

    $items = array();

    //die($listing_sql);
    global $db;

    KCLogger::Log('$listing_sql: ' . $listing_sql);
    $product_infos = $db->Execute($listing_sql, ($page_no > 1 ? ($page_no - 1) * $page_size . "," : '') . $page_size . " ");
    //die($product_infos->fields['products_id']);
    if ($product_infos->RecordCount() > 0) {
        while (!$product_infos->EOF) {
            $item = turnItem($product_infos);
            $items[] = $item;
            $product_infos->MoveNext();
        }
    }

    $returnResult = array();
    $returnResult['total_results'] = $total_result->number_of_rows;
    $returnResult['items'] = $items;
    // TODO 增加 item_cats
    $kcResponse->DataBack($returnResult);
}

// cls_Item.php end