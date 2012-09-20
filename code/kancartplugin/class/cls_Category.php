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

class KC_Itemcat {

    var $cid = '';
    var $parent_cid = '';
    var $name = '';
    var $count = '';
    var $is_parent = true;
    var $sort_order = '';

    function KC_Itemcat() {
        
    }

}

/**
 * 获取商品类目
 * @param string $parent_id 父类目的ID
 * @param array $fields 所需字段的数组
 * @return type
 */
function kancart_categories_get($parent_id, $fields) {
    Global $kcResponse, $db;
    $categories_query = "select *
                         from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd
                         where c.categories_status = '1' and 
                         parent_id = '" . (int) $parent_id . "'
                         and c.categories_id = cd.categories_id
                         and cd.language_id = '" . (int) $_SESSION['languages_id'] . "'
                         order by sort_order, cd.categories_name";

    $categories = $db->Execute($categories_query);

    $itemcats = array();
    while (!$categories->EOF) {
        $itemcat = new KC_Itemcat();
        $itemcat->cid = $categories->fields['categories_id'];
        $itemcat->parent_cid = $categories->fields['parent_id'];
        $itemcat->name = $categories->fields['categories_name'];
        $itemcat->count = zen_count_products_in_category($itemcat->cid);
        $itemcat->is_parent = zen_has_category_subcategories($itemcat->cid);
        $itemcat->sort_order = $categories->fields['sort_order'];

        $itemcats[] = $itemcat;
        $categories->MoveNext();
    }

    if (count($itemcats) <= 0) {
        // TODO 没有子类目的情况
        $kcResponse->ErrorBack('0x2003');
    }

    $back = array('item_cats' => $itemcats);
    $kcResponse->DataBack($back);
}

/**
 * 获取所有商品类目
 * @param array $fields 所需字段的数组
 * @return type 
 */
function kancart_categories_all($fields) {

    Global $kcResponse, $db;
    $categories_query = "select *
                         from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd
                         where c.categories_status = '1'
                         and c.categories_id = cd.categories_id
                         and cd.language_id = '" . (int) $_SESSION['languages_id'] . "'
                         order by sort_order, cd.categories_name";

    $categories = $db->Execute($categories_query);

    $itemcats = array();
    while (!$categories->EOF) {
        $itemcat = new KC_Itemcat();
        $itemcat->cid = $categories->fields['categories_id'];
        $itemcat->parent_cid = $categories->fields['parent_id'];
        if ($itemcat->parent_cid == '0') {
            $itemcat->parent_cid = '-1';
        }

        $itemcat->name = $categories->fields['categories_name'];
        $itemcat->count = zen_count_products_in_category($itemcat->cid);
        $itemcat->is_parent = zen_has_category_subcategories($itemcat->cid);
        $itemcat->sort_order = $categories->fields['sort_order'];

        $itemcats[] = $itemcat;
        $categories->MoveNext();
    }

    if (count($itemcats) <= 0) {
        // TODO 没有子类目的情况
        $kcResponse->ErrorBack('0x2003');
    }

    $back = array('item_cats' => $itemcats);
    $kcResponse->DataBack($back);
}

// cls_Category.php end