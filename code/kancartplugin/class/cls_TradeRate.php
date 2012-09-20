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

class KC_TradeRate {

    var $uname = '';
    var $item_id = '';
    var $rate_score = '';
    var $rate_title = '';
    var $rate_content = '';
    var $rate_date = '';

    function KC_TradeRate() {
        
    }

}

/**
 * 获取用户的评论[review]
 */
function kancart_traderates_get() {
    global $db;
    $page_no = 1;
    $page_size = 10;
    $item_id = false;

    if (isset($_POST['page_no']) && (int) $_POST['page_no'] > 0) {
        $page_no = $_POST['page_no'];
    }
    if (isset($_POST['page_size']) && (int) $_POST['page_size'] > 0) {
        $page_size = $_POST['page_size'];
    }
    if (isset($_POST['item_id']) && !isEmptyString($_POST['item_id'])) {
        $item_id = $_POST['item_id'];
    }
    if (!$item_id) {
        // TODO 参数错误
        die();
    }

    // 单页数量不能超过30条
    $page_size = $page_size > 30 ? 30 : $page_size;

    $returnResult = array();

    // 获取该商品review 总数
    $reviews_total = kancart_item_reviews_count($item_id);
    $returnResult['total_results'] = $reviews_total;

    if ($reviews_total > 0) {
        $reviews_query = "SELECT DISTINCT r.reviews_id,r.customers_name,
                          r.reviews_rating,rd.languages_id,
                          rd.reviews_text,r.date_added,r.products_id,
                          r.reviews_id 
                          FROM " . TABLE_REVIEWS . " AS r 
                          Inner Join " . TABLE_REVIEWS_DESCRIPTION . " AS rd 
                          ON r.reviews_id = rd.reviews_id 
                          WHERE 
                          r.products_id =  ':productsID' 
                          AND r.status = '1' 
                          GROUP BY r.reviews_id 
                          ORDER BY r.reviews_id DESC ";
        $limitStr = " LIMIT " . ($page_no > 1 ? ($page_no - 1) * $page_size . "," : '') . $page_size;
        $reviews_query .= $limitStr;
        $reviews_query = $db->bindVars($reviews_query, ':productsID', $item_id, 'integer');
        $reviews = $db->Execute($reviews_query);

        $traderates_array = array();
        while (!$reviews->EOF) {
            $review = new KC_TradeRate();
            $review->uname = $reviews->fields['customers_name'];
            $review->item_id = $reviews->fields['products_id'];
            $review->rate_score = $reviews->fields['reviews_rating'];
            $review->rate_title = '';
            $review->rate_content = $reviews->fields['reviews_text'];
            $review->rate_date = $reviews->fields['date_added'];
            $traderates_array[] = $review;
            $reviews->moveNext();
        }
        $returnResult['trade_rates'] = $traderates_array;
    } else {
        $returnResult['trade_rates'] = array();
    }
    return $returnResult;
}

function kancart_traderate_add() {
    Global $kcResponse, $db, $template, $template_dir, $current_page_base, $language_page_directory;
    $language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';
    $current_page_base = 'product_reviews_write';
    require_once DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

    KCLogger::Log('kancart_traderate_add');
    $item_id = zen_db_prepare_input($_POST['item_id']);
    $rating = zen_db_prepare_input($_POST['rating']);
    $review_text = zen_db_prepare_input($_POST['content']);
    $review_title = '';
    if (REVIEW_WITH_TITLE) {
        $review_title = zen_db_prepare_input($_POST['title']);
    }

    $product_info_query = "SELECT p.products_id, p.products_model, p.products_image,
                              p.products_price, p.products_tax_class_id, pd.products_name
                           FROM " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd
                           WHERE p.products_id = :productsID
                           AND p.products_status = '1'
                           AND p.products_id = pd.products_id
                           AND pd.language_id = :languagesID";

    $product_info_query = $db->bindVars($product_info_query, ':productsID', $item_id, 'integer');
    $product_info_query = $db->bindVars($product_info_query, ':languagesID', $_SESSION['languages_id'], 'integer');
    $product_info = $db->Execute($product_info_query);

    if (!$product_info->RecordCount()) {
        // 商品不存在
    }

    $customer_query = "SELECT customers_firstname, customers_lastname, customers_email_address
                   FROM " . TABLE_CUSTOMERS . "
                   WHERE customers_id = :customersID";
    $customer_query = $db->bindVars($customer_query, ':customersID', $_SESSION['customer_id'], 'integer');
    $customer = $db->Execute($customer_query);

    $error = false;
    if (strlen($review_text) < REVIEW_TEXT_MIN_LENGTH) {
        $error = true;
        $kcResponse->ErrorBack('0x9999', JS_REVIEW_TEXT);
    }

    if (($rating < 1) || ($rating > 5)) {
        $error = true;
        $kcResponse->ErrorBack('0x9999', JS_REVIEW_RATING);
    }

    if ($error == false) {
        if (REVIEWS_APPROVAL == '1') {
            $review_status = '0';
        } else {
            $review_status = '1';
        }

        $sql = "INSERT INTO " . TABLE_REVIEWS . " (products_id, customers_id, customers_name, reviews_rating, date_added, status)
            VALUES (:productsID, :customersID, :customersName, :rating, now(), " . $review_status . ")";

        $sql = $db->bindVars($sql, ':productsID', $item_id, 'integer');
        $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');
        $sql = $db->bindVars($sql, ':customersName', $customer->fields['customers_firstname'] . ' ' . $customer->fields['customers_lastname'], 'string');
        $sql = $db->bindVars($sql, ':rating', $rating, 'string');

        $db->Execute($sql);

        $insert_id = $db->Insert_ID();
        $sql = '';
        if (REVIEW_WITH_TITLE) {
            $sql = "INSERT INTO " . TABLE_REVIEWS_DESCRIPTION . " (reviews_id, languages_id, reviews_text, " . REVIEW_TITLE_KEY . ")
            VALUES (:insertID, :languagesID, :reviewText, :reviewTitle)";
            $sql = $db->bindVars($sql, ':insertID', $insert_id, 'integer');
            $sql = $db->bindVars($sql, ':languagesID', $_SESSION['languages_id'], 'integer');
            $sql = $db->bindVars($sql, ':reviewText', $review_text, 'string');
            $sql = $db->bindVars($sql, ':reviewTitle', $review_title, 'string');
        } else {
            $sql = "INSERT INTO " . TABLE_REVIEWS_DESCRIPTION . " (reviews_id, languages_id, reviews_text)
            VALUES (:insertID, :languagesID, :reviewText)";
            $sql = $db->bindVars($sql, ':insertID', $insert_id, 'integer');
            $sql = $db->bindVars($sql, ':languagesID', $_SESSION['languages_id'], 'integer');
            $sql = $db->bindVars($sql, ':reviewText', $review_text, 'string');
        }

        $db->Execute($sql);
        // send review-notification email to admin
        if (REVIEWS_APPROVAL == '1' && SEND_EXTRA_REVIEW_NOTIFICATION_EMAILS_TO_STATUS == '1' and defined('SEND_EXTRA_REVIEW_NOTIFICATION_EMAILS_TO') and SEND_EXTRA_REVIEW_NOTIFICATION_EMAILS_TO != '') {
            $email_text = sprintf(EMAIL_PRODUCT_REVIEW_CONTENT_INTRO, $product_info->fields['products_name']) . "\n\n";
            $email_text .= sprintf(EMAIL_PRODUCT_REVIEW_CONTENT_DETAILS, $review_text) . "\n\n";
            $email_subject = sprintf(EMAIL_REVIEW_PENDING_SUBJECT, $product_info->fields['products_name']);
            $html_msg['EMAIL_SUBJECT'] = sprintf(EMAIL_REVIEW_PENDING_SUBJECT, $product_info->fields['products_name']);
            $html_msg['EMAIL_MESSAGE_HTML'] = str_replace('\n', '', sprintf(EMAIL_PRODUCT_REVIEW_CONTENT_INTRO, $product_info->fields['products_name']));
            $html_msg['EMAIL_MESSAGE_HTML'] .= '<br />';
            $html_msg['EMAIL_MESSAGE_HTML'] .= str_replace('\n', '', sprintf(EMAIL_PRODUCT_REVIEW_CONTENT_DETAILS, $review_text));
            $extra_info = email_collect_extra_info($name, $email_address, $customer->fields['customers_firstname'] . ' ' . $customer->fields['customers_lastname'], $customer->fields['customers_email_address']);
            $html_msg['EXTRA_INFO'] = $extra_info['HTML'];
            zen_mail('', SEND_EXTRA_REVIEW_NOTIFICATION_EMAILS_TO, $email_subject, $email_text . $extra_info['TEXT'], STORE_NAME, EMAIL_FROM, $html_msg, 'reviews_extra');
        }
        // end send email
        return array();
    }
    KCLogger::Log('Check Error');
}

/**
 * 获取指定商品的回复总数
 * @global db $db
 * @param string $item_id
 * @return int 
 */
function kancart_item_reviews_count($item_id) {
    Global $db;
    // 获取该商品review 总数
    $reviews_check_query = "SELECT count(*) AS total
                            FROM " . TABLE_REVIEWS . "
                            WHERE products_id = '" . $item_id . "'
                            AND status = '1'";
    $reviews_check = $db->Execute($reviews_check_query);
    return (int) $reviews_check->fields['total'];
}

/**
 * 获得指定商品的回复平均分
 * @global db $db
 * @param string $item_id
 * @return float 
 */
function kancart_item_reviews_score($item_id) {
    Global $db;
    // 获取该商品review 总数
    $reviews_check_query = "SELECT Avg(r.reviews_rating) AS rating_score
                            FROM " . TABLE_REVIEWS . " AS r
                            WHERE r.status =  '1' 
                            AND products_id = '" . $item_id . "'";
    $reviews_check = $db->Execute($reviews_check_query);
    return $reviews_check->fields['rating_score'];
}

// cls_TradeRate.php