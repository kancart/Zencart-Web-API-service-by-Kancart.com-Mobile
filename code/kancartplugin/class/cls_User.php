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

class KC_User {

    var $uname = '';
    var $nick = '';
    var $email = '';
    var $fax = '';
    var $telephone = '';
    var $default_address_id = '';
    var $dob = '';
    var $lastname = '';
    var $firstname = '';
    var $gender = '';
    var $mobile = '';
    var $shopping_cart = '';
    var $address_list = '';

    function KC_User() {
        
    }

}

class KC_Address {

    var $address_book_id = '';
    var $address_type = '';
    var $lastname = '';
    var $firstname = '';
    var $telephone = '';
    var $mobile = '';
    var $gender = '';
    var $postcode = '';
    var $city = '';
    var $zone_id = '';
    var $zone_code = '';
    var $zone_name = '';
    var $state = '';
    var $address1 = '';
    var $address2 = '';
    var $country_id = '';
    var $country_code = '';
    var $country_name = '';
    var $company;

    function KC_Address() {
        
    }

}

function kancart_user_login($email_address, $password) {
    Global $kcResponse, $db;
    $result = array();

    //login/header_php.php-48
    $check_customer_query = "SELECT customers_id, customers_firstname, customers_lastname, customers_password,
                                    customers_email_address, customers_default_address_id,
                                    customers_authorization, customers_referral
                             FROM " . TABLE_CUSTOMERS . "
                             WHERE customers_email_address = :emailAddress";

    $check_customer_query = $db->bindVars($check_customer_query, ':emailAddress', $email_address, 'string');
    $check_customer = $db->Execute($check_customer_query);

    if (!$check_customer->RecordCount()) {
        // 找不到账号
        $kcResponse->ErrorBack('0x1009');
    } elseif ($check_customer->fields['customers_authorization'] == '4') {
        // 账号禁止
        $kcResponse->ErrorBack('0x1008');
    } else {
        // Check that password is good
        if (!zen_validate_password($password, $check_customer->fields['customers_password'])) {
            // 密码错误 wrong password
            $kcResponse->ErrorBack('0x1009');
        } else {
            // 密码正确 correct password
            $_SESSION['kancart_session_key'] = md5($email_address . time());
            $_SESSION['kancart_last_login_date'] = time();
            $_SESSION['kancart_login_uname'] = $_POST['uname'];

            $check_country_query = "SELECT entry_country_id, entry_zone_id
                                    FROM " . TABLE_ADDRESS_BOOK . "
                                    WHERE customers_id = :customersID
                                    AND address_book_id = :addressBookID";

            $check_country_query = $db->bindVars($check_country_query, ':customersID', $check_customer->fields['customers_id'], 'integer');
            $check_country_query = $db->bindVars($check_country_query, ':addressBookID', $check_customer->fields['customers_default_address_id'], 'integer');
            $check_country = $db->Execute($check_country_query);

            $_SESSION['customer_id'] = $check_customer->fields['customers_id'];
            $_SESSION['customer_default_address_id'] = $check_customer->fields['customers_default_address_id'];
            $_SESSION['customers_authorization'] = $check_customer->fields['customers_authorization'];
            $_SESSION['customer_first_name'] = $check_customer->fields['customers_firstname'];
            $_SESSION['customer_last_name'] = $check_customer->fields['customers_lastname'];
            $_SESSION['customer_country_id'] = $check_country->fields['entry_country_id'];
            $_SESSION['customer_zone_id'] = $check_country->fields['entry_zone_id'];

            // 更新登录日志
            $sql = "UPDATE " . TABLE_CUSTOMERS_INFO . "
                    SET customers_info_date_of_last_logon = now(),
                    customers_info_number_of_logons = customers_info_number_of_logons+1
                    WHERE customers_info_id = :customersID";

            $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');
            $db->Execute($sql);

            // 用户登录 存储未登录前的商品
            $_SESSION['cart']->restore_contents();

            $back = array('sessionkey' => $_SESSION['kancart_session_key']);
            $kcResponse->DataBack($back);
        }
    }
}

function kancart_user_logout() {
    zen_session_destroy();
    return true;
}

function kancart_user_get($fields = '') {
    global $db;
    $query = "select * from " . TABLE_CUSTOMERS . " where customers_id='" . $_SESSION['customer_id'] . "'";
    $result = $db->Execute($query);
    $user = new KC_User();
    while (!$result->EOF) {
        $user->uname = $result->fields["customers_email_address"];
        $user->nick = $result->fields["customers_nick"];
        $user->email = $result->fields["customers_email_address"];
        $user->fax = $result->fields["customers_fax"];
        $user->telephone = $result->fields["customers_telephone"];
        $user->default_address_id = $result->fields["customers_default_address_id"];
        $user->dob = $result->fields["customers_dob"];
        $user->lastname = $result->fields["customers_firstname"];
        $user->firstname = $result->fields["customers_lastname"];
        $user->gender = $result->fields["customers_gender"];

        break;
    }
    return $user;
}

function kancart_user_update($user) {
    /*
      `customers_gender`
      `customers_firstname`
      `customers_lastname`
      `customers_dob`
      `customers_email_address`
      `customers_nick`
      `customers_default_address_id`
      `customers_telephone`
      `customers_fax`
      `customers_newsletter`
      `customers_group_pricing`
      `customers_email_format`
      `customers_authorization`
      `customers_referral`
      `customers_paypal_payerid`
      `customers_paypal_ec`
     */
    //update phpBB with new email address
    $old_addr_check = $db->Execute("select * from " . TABLE_CUSTOMERS . " where customers_id='" . (int) $_SESSION['customer_id'] . "'");
    //$phpBB->phpbb_change_email(zen_db_input($old_addr_check->fields['customers_email_address']),zen_db_input($old_addr_check->fields['customers_email_address']));

    $sql_data_array = array();
    //if (isset($user->firstname))
    // TODO

    $sql_data_array = array(array('fieldName' => 'customers_firstname', 'value' => $user->firstname, 'type' => 'string'),
        array('fieldName' => 'customers_lastname', 'value' => $user->lastname, 'type' => 'string'),
        array('fieldName' => 'customers_email_address', 'value' => zen_db_input($old_addr_check->fields['customers_email_address']), 'type' => 'string'),
        array('fieldName' => 'customers_telephone', 'value' => $user->telephone, 'type' => 'string'),
        array('fieldName' => 'customers_fax', 'value' => $user->fax, 'type' => 'string'),
        array('fieldName' => 'customers_default_address_id', 'value' => $user->default_address_id, 'type' => 'string'),
        array('fieldName' => 'customers_email_format', 'value' => $old_addr_check->fields['customers_email_format'], 'type' => 'string')
    );

    if ((CUSTOMERS_REFERRAL_STATUS == '2' and $customers_referral != '')) {
        $sql_data_array[] = array('fieldName' => 'customers_referral', 'value' => $old_addr_check->fields['customers_referral'], 'type' => 'string');
    }
    if (ACCOUNT_GENDER == 'true') {
        $sql_data_array[] = array('fieldName' => 'customers_gender', 'value' => $user->gender, 'type' => 'string');
    }
    if (ACCOUNT_DOB == 'true') {
        if ($dob == '0001-01-01 00:00:00' or $_POST['dob'] == '') {
            $sql_data_array[] = array('fieldName' => 'customers_dob', 'value' => '0001-01-01 00:00:00', 'type' => 'date');
        } else {
            $sql_data_array[] = array('fieldName' => 'customers_dob', 'value' => zen_date_raw($user->dob), 'type' => 'date');
        }
    }

    $where_clause = "customers_id = :customersID";
    $where_clause = $db->bindVars($where_clause, ':customersID', $_SESSION['customer_id'], 'integer');
    $db->perform(TABLE_CUSTOMERS, $sql_data_array, 'update', $where_clause);

    $sql = "UPDATE " . TABLE_CUSTOMERS_INFO . "
            SET    customers_info_date_account_last_modified = now()
            WHERE  customers_info_id = :customersID";

    $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');

    $db->Execute($sql);

    $where_clause = "customers_id = :customersID AND address_book_id = :customerDefaultAddressID";
    $where_clause = $db->bindVars($where_clause, ':customersID', $_SESSION['customer_id'], 'integer');
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    if (kancart_validate_address_id($user->default_address_id)) {
        $where_clause = $db->bindVars($where_clause, ':customerDefaultAddressID', $user->default_address_id, 'integer');
        $_SESSION['customer_default_address_id'] = $user->default_address_id;
    } else {
        $where_clause = $db->bindVars($where_clause, ':customerDefaultAddressID', $_SESSION['customer_default_address_id'], 'integer');
    }
    $sql_data_array = array(array('fieldName' => 'entry_firstname', 'value' => $firstname, 'type' => 'string'),
        array('fieldName' => 'entry_lastname', 'value' => $lastname, 'type' => 'string'));

    $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', $where_clause);

    // reset the session variables
    $_SESSION['customer_first_name'] = $firstname;

    return true;
}

function kancart_user_register($user) {
    Global $kcResponse, $template, $template_dir, $current_page_base, $language_page_directory, $currencies, $db;
    $language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';
    $current_page_base = 'create_account';
    require_once DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

    $sql_data_array = array('customers_firstname' => $user->firstname,
        'customers_lastname' => $user->lastname,
        'customers_email_address' => $user->uname,
        'customers_nick' => $user->nick,
        'customers_telephone' => $user->telephone,
        'customers_fax' => $user->fax,
        //'customers_dob' => $user->dob,
        'customers_gender' => $user->gender,
        'customers_newsletter' => 0,
        //'customers_email_format' => $email_format,
        'customers_default_address_id' => 0,
        'customers_password' => zen_encrypt_password($user->pwd)
            //'customers_authorization' => (int)CUSTOMERS_APPROVAL_AUTHORIZATION
    );

    $result = zen_db_perform(TABLE_CUSTOMERS, $sql_data_array);
    $customerID = $db->Insert_ID();

    $sql = "insert into " . TABLE_CUSTOMERS_INFO . "
                          (customers_info_id, customers_info_number_of_logons,
                           customers_info_date_account_created, customers_info_date_of_last_logon)
              values ('" . (int)$customerID . "', '1', now(), now())";

    $db->Execute($sql);

    if (SESSION_RECREATE == 'True') {
        zen_session_recreate();
    }

    if ($phpBB->phpBB['installed'] == true) {
        $phpBB->phpbb_create_account($user->firstname . ' ' . $user->lastname, $user->pwd, $user->uname);
    }
    $_SESSION['cart']->restore_contents();

    $name = $user->firstname . ' ' . $user->lastname;

    if (ACCOUNT_GENDER == 'true') {
        if ($gender == 'm') {
            $email_text = sprintf(EMAIL_GREET_MR, $user->lastname);
        } else {
            $email_text = sprintf(EMAIL_GREET_MS, $user->lastname);
        }
    } else {
        $email_text = sprintf(EMAIL_GREET_NONE, $user->firstname);
    }
    $html_msg['EMAIL_GREETING'] = str_replace('\n', '', $email_text);
    $html_msg['EMAIL_FIRST_NAME'] = $user->firstname;
    $html_msg['EMAIL_LAST_NAME'] = $user->lastname;

    // initial welcome
    $email_text .= EMAIL_WELCOME;
    $html_msg['EMAIL_WELCOME'] = str_replace('\n', '', EMAIL_WELCOME);

    if (NEW_SIGNUP_DISCOUNT_COUPON != '' and NEW_SIGNUP_DISCOUNT_COUPON != '0') {
        $coupon_id = NEW_SIGNUP_DISCOUNT_COUPON;
        $coupon = $db->Execute("select * from " . TABLE_COUPONS . " where coupon_id = '" . $coupon_id . "'");
        $coupon_desc = $db->Execute("select coupon_description from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $coupon_id . "' and language_id = '" . $_SESSION['languages_id'] . "'");
        $db->Execute("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ('" . $coupon_id . "', '0', 'Admin', '" . $user->uname . "', now() )");

        $text_coupon_help = sprintf(TEXT_COUPON_HELP_DATE, zen_date_short($coupon->fields['coupon_start_date']), zen_date_short($coupon->fields['coupon_expire_date']));

        // if on, add in Discount Coupon explanation
        //        $email_text .= EMAIL_COUPON_INCENTIVE_HEADER .
        $email_text .= "\n" . EMAIL_COUPON_INCENTIVE_HEADER .
                (!empty($coupon_desc->fields['coupon_description']) ? $coupon_desc->fields['coupon_description'] . "\n\n" : '') . $text_coupon_help . "\n\n" .
                strip_tags(sprintf(EMAIL_COUPON_REDEEM, ' ' . $coupon->fields['coupon_code'])) . EMAIL_SEPARATOR;

        $html_msg['COUPON_TEXT_VOUCHER_IS'] = EMAIL_COUPON_INCENTIVE_HEADER;
        $html_msg['COUPON_DESCRIPTION'] = (!empty($coupon_desc->fields['coupon_description']) ? '<strong>' . $coupon_desc->fields['coupon_description'] . '</strong>' : '');
        $html_msg['COUPON_TEXT_TO_REDEEM'] = str_replace("\n", '', sprintf(EMAIL_COUPON_REDEEM, ''));
        $html_msg['COUPON_CODE'] = $coupon->fields['coupon_code'] . $text_coupon_help;
    } //endif coupon

    if (NEW_SIGNUP_GIFT_VOUCHER_AMOUNT > 0) {
        $coupon_code = zen_create_coupon_code();
        $insert_query = $db->Execute("insert into " . TABLE_COUPONS . " (coupon_code, coupon_type, coupon_amount, date_created) values ('" . $coupon_code . "', 'G', '" . NEW_SIGNUP_GIFT_VOUCHER_AMOUNT . "', now())");
        $insert_id = $db->Insert_ID();
        $db->Execute("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ('" . $insert_id . "', '0', 'Admin', '" . $user->uname . "', now() )");

        // if on, add in GV explanation
        $email_text .= "\n\n" . sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) .
                sprintf(EMAIL_GV_REDEEM, $coupon_code) .
                EMAIL_GV_LINK . zen_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $coupon_code, 'NONSSL', false) . "\n\n" .
                EMAIL_GV_LINK_OTHER . EMAIL_SEPARATOR;
        $html_msg['GV_WORTH'] = str_replace('\n', '', sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)));
        $html_msg['GV_REDEEM'] = str_replace('\n', '', str_replace('\n\n', '<br />', sprintf(EMAIL_GV_REDEEM, '<strong>' . $coupon_code . '</strong>')));
        $html_msg['GV_CODE_NUM'] = $coupon_code;
        $html_msg['GV_CODE_URL'] = str_replace('\n', '', EMAIL_GV_LINK . '<a href="' . zen_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $coupon_code, 'NONSSL', false) . '">' . TEXT_GV_NAME . ': ' . $coupon_code . '</a>');
        $html_msg['GV_LINK_OTHER'] = EMAIL_GV_LINK_OTHER;
    } // endif voucher
    // add in regular email welcome text
    $email_text .= "\n\n" . EMAIL_TEXT . EMAIL_CONTACT . EMAIL_GV_CLOSURE;

    $html_msg['EMAIL_MESSAGE_HTML'] = str_replace('\n', '', EMAIL_TEXT);
    $html_msg['EMAIL_CONTACT_OWNER'] = str_replace('\n', '', EMAIL_CONTACT);
    $html_msg['EMAIL_CLOSURE'] = nl2br(EMAIL_GV_CLOSURE);

    // include create-account-specific disclaimer
    $email_text .= "\n\n" . sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, STORE_OWNER_EMAIL_ADDRESS) . "\n\n";
    $html_msg['EMAIL_DISCLAIMER'] = sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, '<a href="mailto:' . STORE_OWNER_EMAIL_ADDRESS . '">' . STORE_OWNER_EMAIL_ADDRESS . ' </a>');

    // send welcome email
    if (trim(EMAIL_SUBJECT) != 'n/a')
        zen_mail($name, $user->uname, EMAIL_SUBJECT, $email_text, STORE_NAME, EMAIL_FROM, $html_msg, 'welcome');

    // send additional emails
    if (SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_STATUS == '1' and SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO != '') {
        if ($_SESSION['customer_id']) {
            $account_query = "select customers_firstname, customers_lastname, customers_email_address, customers_telephone, customers_fax
                            from " . TABLE_CUSTOMERS . "
                            where customers_id = '" . (int) $_SESSION['customer_id'] . "'";

            $account = $db->Execute($account_query);
        }

        $extra_info = email_collect_extra_info($name, $user->uname, $account->fields['customers_firstname'] . ' ' . $account->fields['customers_lastname'], $account->fields['customers_email_address'], $account->fields['customers_telephone'], $account->fields['customers_fax']);
        $html_msg['EXTRA_INFO'] = $extra_info['HTML'];
        if (trim(SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_SUBJECT) != 'n/a')
            zen_mail('', SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO, SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_SUBJECT . ' ' . EMAIL_SUBJECT, $email_text . $extra_info['TEXT'], STORE_NAME, EMAIL_FROM, $html_msg, 'welcome_extra');
    } //endif send extra emails

    return $result;
    /* if($result->RecordCount()>0)
      {
      return true;
      }
      else{
      return false;
      } */
}

function kancart_user_isExists($coustomer_email_address) {
    global $db;
    $query = "select customers_id from " . TABLE_CUSTOMERS . " where customers_email_address='" . $coustomer_email_address . "'";
    $result = $db->Execute($query);
    if ($result->RecordCount() > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取用户地址列表
 * @param string $customer_id 客户ID,一般从session中获取
 * @param string $address_type 获取的地址类型  bill / ship  如果不设置，则默认为两种类型都获取
 * @param array $fields
 * @return Ambigous <multitype:, unknown>
 */
function kancart_user_addresses_get($address_type, $fields) {
    global $db;
    $customer_address_book_count_query = "SELECT count(ab.address_book_id) as total from " . TABLE_CUSTOMERS . " c
                                          left join " . TABLE_ADDRESS_BOOK . " ab on c.customers_id = ab.customers_id
                                          WHERE c.customers_id = '" . (int) $_SESSION['customer_id'] . "' 
                                          ORDER BY ab.address_book_id DESC";

    $result = $db->Execute($customer_address_book_count_query);
    $result->EOF;
    $resultCount = $result->fields['total'];
    $address_array = array();
    if ($resultCount > 0) {
        $customer_address_book_count_query = "SELECT ab.* from " . TABLE_CUSTOMERS . " c
                                          left join " . TABLE_ADDRESS_BOOK . " ab on c.customers_id = ab.customers_id
                                          WHERE c.customers_id = '" . (int) $_SESSION['customer_id'] . "'";
        $result = $db->Execute($customer_address_book_count_query);
        while (!$result->EOF) {
            $address = new KC_Address();
            $address->address_book_id = $result->fields["address_book_id"];
            //$address->address_type = $result->fields[""];
            $address->lastname = $result->fields["entry_lastname"];
            $address->firstname = $result->fields["entry_firstname"];
            //$address->telephone = $result->fields[""];
            //$address->mobile = $result->fields[""];
            $address->gender = $result->fields["entry_gender"];
            $address->postcode = $result->fields["entry_postcode"];
            $address->city = $result->fields["entry_city"];
            $address->zone_id = $result->fields["entry_zone_id"];
            $address->state = $result->fields["entry_state"];
            $address->address1 = $result->fields["entry_street_address"];
            //$address->address2 = $result->fields["address_book_id"];
            $address->address2 = $result->fields["entry_suburb"];
            $address->country_id = $result->fields["entry_country_id"];
            $address->company = $result->fields["entry_company"];
            if (ADDRESS_WITH_PHONE) {
                $address->telephone = $result->fields[ADDRESS_PHONE_KEY];
            } else {
                $address->telephone = '';
            }
            $address_array[] = $address;
            $result->MoveNext();
        }
    }
    return $address_array;
}

/**
 * 获取用户的单个地址
 * @param string $address_book_id
 * @param array $fields
 * @return Ambigous <multitype:, unknown>
 */
function kancart_user_address_get($address_book_id) {
    global $db;

    $customer_address_book_query = "SELECT ab.* from " . TABLE_CUSTOMERS . " c
                               		left join " . TABLE_ADDRESS_BOOK . " ab on c.customers_id = ab.customers_id
                					WHERE c.customers_id = '" . (int) $_SESSION['customer_id'] . "' 
                					and ab.address_book_id = '" . (int) $address_book_id . "'";

    $result = $db->Execute($customer_address_book_query);

    if ($result->RecordCount() > 0) {
        while (!$result->EOF) {
            $address = new KC_Address();
            $address->address_book_id = $result->fields["address_book_id"];
            //$address->address_type = $result->fields[""];
            $address->lastname = $result->fields["entry_lastname"];
            $address->firstname = $result->fields["entry_firstname"];
            //$address->telephone = $result->fields[""];
            //$address->mobile = $result->fields[""];
            $address->gender = $result->fields["entry_gender"];
            $address->postcode = $result->fields["entry_postcode"];
            $address->city = $result->fields["entry_city"];
            $address->zone_id = $result->fields["entry_zone_id"];
            //			$address->zone_code = $result->fields["zone_code"];
            //			$address->zone_name = $result->fields["zone_name"];
            $address->state = $result->fields["entry_state"];
            $address->address1 = $result->fields["entry_street_address"];
            $address->address2 = $result->fields["entry_suburb"];
            $address->country_id = $result->fields["entry_country_id"];
            //			$address->country_code = $result->fields["countries_iso_code_2"];
            //			$address->country_name = $result->fields["countries_name"];
            $address->company = $result->fields["entry_company"];
            if (ADDRESS_WITH_PHONE) {
                $address->telephone = $result->fields[ADDRESS_PHONE_KEY];
            } else {
                $address->telephone = '';
            }
            return $address;
        }
    } else {
        return false;
    }
}

/**
 * 添加一个用户地址
 * @param array $address
 * @return new address id
 */
function kancart_user_address_add(array $address) {
    global $db;

    if (isset($address['address_book_id']) && !isEmptyString($address['address_book_id'])) {
        user_address_update($address);
        return;
    }

    // 准备参数
    //if (ACCOUNT_GENDER == 'true')
    $gender = zen_db_prepare_input($address['gender']);
    if ($gender != 'm' || $gender != 'f') {
        $gender = 'm';
    }
    //if (ACCOUNT_COMPANY == 'true')
    $company = zen_db_prepare_input($address['company']);
    $firstname = zen_db_prepare_input(zen_sanitize_string($address['firstname']));
    $lastname = zen_db_prepare_input(zen_sanitize_string($address['lastname']));
    $street_address = zen_db_prepare_input($address['address1']);
    //if (ACCOUNT_SUBURB == 'true')
    $suburb = zen_db_prepare_input($address['address2']);
    $postcode = zen_db_prepare_input($address['postcode']);
    $city = zen_db_prepare_input($address['city']);
    $telephone = zen_db_prepare_input($address['telephone']);

    /**
     * error checking when updating or adding an entry
     */
    // 检查国家地区是否正确
    if (ACCOUNT_STATE == 'true' || ACCOUNT_STATE == 'false') {
        $state = (isset($address['state'])) ? zen_db_prepare_input($address['state']) : zen_db_prepare_input($address['zone_code']);
        if (isset($address['zone_id'])) {
            $zone_id = zen_db_prepare_input($address['zone_id']);
        } else {
            $zone_id = false;
        }
    }
    $country = zen_db_prepare_input($address['country_id']);
    //echo ' I SEE: country=' . $country . '&nbsp;&nbsp;&nbsp;state=' . $state . '&nbsp;&nbsp;&nbsp;zone_id=' . $zone_id;

    if (ACCOUNT_GENDER == 'true') {
        if (($gender != 'm') && ($gender != 'f')) {
            $error = true;
        }
    }

    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
        $error = true;
    }

    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
        $error = true;
    }

    if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
        $error = true;
    }

    if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
        $error = true;
    }

    if (ACCOUNT_STATE == 'true' || ACCOUNT_STATE == 'false') {
        $check_query = "SELECT count(*) AS total
                    	FROM " . TABLE_ZONES . "
                    	WHERE zone_country_id = :zoneCountryID";
        $check_query = $db->bindVars($check_query, ':zoneCountryID', $country, 'integer');
        $check = $db->Execute($check_query);
        $entry_state_has_zones = ($check->fields['total'] > 0);
        if ($entry_state_has_zones == true) {
            $zone_query = "SELECT distinct zone_id, zone_name, zone_code
                    		FROM " . TABLE_ZONES . "
                     		WHERE zone_country_id = :zoneCountryID
                     		AND " .
                    ((trim($state) != '' && $zone_id == 0) ? "(upper(zone_name) like ':zoneState%' OR upper(zone_code) like '%:zoneState%') OR " : "") .
                    "zone_id = :zoneID
                     		ORDER BY zone_code ASC, zone_name";

            $zone_query = $db->bindVars($zone_query, ':zoneCountryID', $country, 'integer');
            $zone_query = $db->bindVars($zone_query, ':zoneState', strtoupper($state), 'noquotestring');
            $zone_query = $db->bindVars($zone_query, ':zoneID', $zone_id, 'integer');
            $zone = $db->Execute($zone_query);

            //look for an exact match on zone ISO code
            $found_exact_iso_match = ($zone->RecordCount() == 1);
            if ($zone->RecordCount() > 1) {
                while (!$zone->EOF && !$found_exact_iso_match) {
                    $fieldss = $zone->fields;
                    if (strtoupper($zone->fields['zone_code']) == strtoupper($state)) {
                        $found_exact_iso_match = true;
                        continue;
                    }
                    $zone->MoveNext();
                }
            }

            if ($found_exact_iso_match) {
                $zone_id = $zone->fields['zone_id'];
                $zone_name = $zone->fields['zone_name'];
            } else {
                $error = true;
                $error_state_input = true;
            }
        } else {
//            if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
//                $error = true;
//                $error_state_input = true;
//            }
        }
    }

    if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
        $error = true;
    }

    if (!is_numeric($country)) {
        $error = true;
    }

    if ($error == false) {
        $sql_data_array = array(array('fieldName' => 'entry_firstname', 'value' => $firstname, 'type' => 'string'),
            array('fieldName' => 'entry_lastname', 'value' => $lastname, 'type' => 'string'),
            array('fieldName' => 'entry_street_address', 'value' => $street_address, 'type' => 'string'),
            array('fieldName' => 'entry_postcode', 'value' => $postcode, 'type' => 'string'),
            array('fieldName' => 'entry_city', 'value' => $city, 'type' => 'string'),
            array('fieldName' => 'entry_country_id', 'value' => $country, 'type' => 'integer'));
        if (ADDRESS_WITH_PHONE) {
            $sql_data_array[] = array('fieldName' => ADDRESS_PHONE_KEY, 'value' => $telephone, 'type' => 'string');
        }
        //if (ACCOUNT_GENDER == 'true')
        $sql_data_array[] = array('fieldName' => 'entry_gender', 'value' => $gender, 'type' => 'enum:m|f');
        //if (ACCOUNT_COMPANY == 'true')
        $sql_data_array[] = array('fieldName' => 'entry_company', 'value' => $company, 'type' => 'string');
        //if (ACCOUNT_SUBURB == 'true')
        $sql_data_array[] = array('fieldName' => 'entry_suburb', 'value' => $suburb, 'type' => 'string');
        if (ACCOUNT_STATE == 'true' || ACCOUNT_STATE == 'false') {
            if ($zone_id > 0) {
                $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => $zone_id, 'type' => 'integer');
                $sql_data_array[] = array('fieldName' => 'entry_state', 'value' => '', 'type' => 'string');
            } else {
                $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => '0', 'type' => 'integer');
                $sql_data_array[] = array('fieldName' => 'entry_state', 'value' => $state, 'type' => 'string');
            }
        }

        $sql_data_array[] = array('fieldName' => 'customers_id', 'value' => $_SESSION['customer_id'], 'type' => 'integer');

        if (isset($address['address_book_id'])) {
            // 验证已经选择的地址是否存在
            $check_address_query = "SELECT count(*) AS total
                            FROM   " . TABLE_ADDRESS_BOOK . "
                            WHERE  customers_id = :customersID
                            AND    address_book_id = :addressBookID";

            $check_address_query = $db->bindVars($check_address_query, ':customersID', $_SESSION['customer_id'], 'integer');
            $check_address_query = $db->bindVars($check_address_query, ':addressBookID', $address['address_book_id'], 'integer');
            $check_address = $db->Execute($check_address_query);

            if ($check_address->fields['total'] != '1') {
                // 验证已经选择的地址失败, 则添加该地址
                $address['address_book_id'] = '';
            }
        } else {
            $address['address_book_id'] = '';
        }

        // 添加新地址
        $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $new_address_book_id = $db->Insert_ID();
        KCLogger::Log('address add: ' . $new_address_book_id);
        if ($new_address_book_id) {
            $address['address_book_id'] = $new_address_book_id;
            KCLogger::Log('ADDRESS_WITH_PHONE: ' . (ADDRESS_WITH_PHONE? 'true': 'false'));
            KCLogger::Log('address count: ' . kancart_user_address_count());
            if (ADDRESS_WITH_PHONE && kancart_user_address_count() <= 1) {
                KCLogger::Log('address add --update customers_telephone');
                $telephoneData = array();
                $telephoneData['fieldName'] = 'customers_telephone';
                $telephoneData['value'] = $telephone;
                $telephoneData['type'] = 'string';
                $changeTelephoneSqlDataArray = array($telephoneData);
                
                $where_clause = "customers_id = :customersID";
                $where_clause = $db->bindVars($where_clause, ':customersID', $_SESSION['customer_id'], 'integer');
                $db->perform(TABLE_CUSTOMERS, $changeTelephoneSqlDataArray, 'update', $where_clause);
                
                KCLogger::Log('address add --update customers_telephone done');
            }
            kancart_validate_session_default_address_id($new_address_book_id);
            KCLogger::Log('address add: done');
            return $new_address_book_id;
        } else {
            // TODO 添加地址失败
            return false;
        }
    }
    // TODO 地址信息错误
    return false;
}

function kancart_user_address_count() {
    Global $db;
    $check_address_query = "SELECT count(*) AS total
                            FROM   " . TABLE_ADDRESS_BOOK . "
                            WHERE  customers_id = :customersID";
    $check_address_query = $db->bindVars($check_address_query, ':customersID', $_SESSION['customer_id'], 'integer');
    $check_address = $db->Execute($check_address_query);
    return (int) $check_address->fields['total'];
}

/**
 * 更新编辑用户地址
 * @param array $address
 * @return boolean
 */
function kancart_user_address_update(array $address) {
    global $db;
    // 验证已经选择的地址是否存在
    $isExist = false;
    if (isset($address['address_book_id'])) {
        require_once 'kancartplugin/class/cls_ShoppingCart.php';
        $isExist = kancart_validate_address_id($address['address_book_id']);
    }

    if (!$isExist) {
        // 这个ID的地址不存在，则添加这个地址
        $address['address_book_id'] = '';
        user_address_add($address);
        return;
    }

    // 准备参数
    //if (ACCOUNT_GENDER == 'true')
    $gender = zen_db_prepare_input($address['gender']);
    //if (ACCOUNT_COMPANY == 'true')
    $company = zen_db_prepare_input($address['company']);
    $firstname = zen_db_prepare_input(zen_sanitize_string($address['firstname']));
    $lastname = zen_db_prepare_input(zen_sanitize_string($address['lastname']));
    $street_address = zen_db_prepare_input($address['address1']);
    //if (ACCOUNT_SUBURB == 'true')
    $suburb = zen_db_prepare_input($address['address2']);
    $postcode = zen_db_prepare_input($address['postcode']);
    $city = zen_db_prepare_input($address['city']);
    $telephone = zen_db_prepare_input($address['telephone']);

    /**
     * error checking when updating or adding an entry
     */
    // 检查国家地区是否正确
    if (ACCOUNT_STATE == 'true' || ACCOUNT_STATE == 'false') {
        $state = (isset($address['state'])) ? zen_db_prepare_input($address['state']) : zen_db_prepare_input($address['zone_code']);
        if (isset($address['zone_id'])) {
            $zone_id = zen_db_prepare_input($address['zone_id']);
        } else {
            $zone_id = false;
        }
    }
    $country = zen_db_prepare_input($address['country_id']);
    //echo ' I SEE: country=' . $country . '&nbsp;&nbsp;&nbsp;state=' . $state . '&nbsp;&nbsp;&nbsp;zone_id=' . $zone_id;

    if (ACCOUNT_GENDER == 'true') {
        if (($gender != 'm') && ($gender != 'f')) {
            $error = true;
        }
    }

    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
        $error = true;
    }

    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
        $error = true;
    }

    if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
        $error = true;
    }

    if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
        $error = true;
    }

    if (ACCOUNT_STATE == 'true' || ACCOUNT_STATE == 'false') {
        $check_query = "SELECT count(*) AS total
                    	FROM " . TABLE_ZONES . "
                    	WHERE zone_country_id = :zoneCountryID";
        $check_query = $db->bindVars($check_query, ':zoneCountryID', $country, 'integer');
        $check = $db->Execute($check_query);
        $entry_state_has_zones = ($check->fields['total'] > 0);
        if ($entry_state_has_zones == true) {
            $zone_query = "SELECT distinct zone_id, zone_name, zone_code
                    		FROM " . TABLE_ZONES . "
                     		WHERE zone_country_id = :zoneCountryID
                     		AND " .
                    ((trim($state) != '' && $zone_id == 0) ? "(upper(zone_name) like ':zoneState%' OR upper(zone_code) like '%:zoneState%') OR " : "") .
                    "zone_id = :zoneID
                     		ORDER BY zone_code ASC, zone_name";

            $zone_query = $db->bindVars($zone_query, ':zoneCountryID', $country, 'integer');
            $zone_query = $db->bindVars($zone_query, ':zoneState', strtoupper($state), 'noquotestring');
            $zone_query = $db->bindVars($zone_query, ':zoneID', $zone_id, 'integer');
            $zone = $db->Execute($zone_query);

            //look for an exact match on zone ISO code
            $found_exact_iso_match = ($zone->RecordCount() == 1);
            if ($zone->RecordCount() > 1) {
                while (!$zone->EOF && !$found_exact_iso_match) {
                    $fieldss = $zone->fields;
                    if (strtoupper($zone->fields['zone_code']) == strtoupper($state)) {
                        $found_exact_iso_match = true;
                        continue;
                    }
                    $zone->MoveNext();
                }
            }

            if ($found_exact_iso_match) {
                $zone_id = $zone->fields['zone_id'];
                $zone_name = $zone->fields['zone_name'];
            } else {
                $error = true;
                $error_state_input = true;
            }
        } else {
//            if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
//                $error = true;
//                $error_state_input = true;
//            }
        }
    }

    if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
        $error = true;
    }

    if (!is_numeric($country)) {
        $error = true;
    }

    if ($error == false) {
        $sql_data_array = array(array('fieldName' => 'entry_firstname', 'value' => $firstname, 'type' => 'string'),
            array('fieldName' => 'entry_lastname', 'value' => $lastname, 'type' => 'string'),
            array('fieldName' => 'entry_street_address', 'value' => $street_address, 'type' => 'string'),
            array('fieldName' => 'entry_postcode', 'value' => $postcode, 'type' => 'string'),
            array('fieldName' => 'entry_city', 'value' => $city, 'type' => 'string'),
            array('fieldName' => 'entry_country_id', 'value' => $country, 'type' => 'integer'));
        if (ADDRESS_WITH_PHONE) {
            $sql_data_array[] = array('fieldName' => ADDRESS_PHONE_KEY, 'value' => $telephone, 'type' => 'string');
        }
        //if (ACCOUNT_GENDER == 'true')
        $sql_data_array[] = array('fieldName' => 'entry_gender', 'value' => $gender, 'type' => 'enum:m|f');
        //if (ACCOUNT_COMPANY == 'true')
        $sql_data_array[] = array('fieldName' => 'entry_company', 'value' => $company, 'type' => 'string');
        //if (ACCOUNT_SUBURB == 'true')
        $sql_data_array[] = array('fieldName' => 'entry_suburb', 'value' => $suburb, 'type' => 'string');
        if (ACCOUNT_STATE == 'true' || ACCOUNT_STATE == 'false') {
            if ($zone_id > 0) {
                $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => $zone_id, 'type' => 'integer');
                $sql_data_array[] = array('fieldName' => 'entry_state', 'value' => '', 'type' => 'string');
            } else {
                $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => '0', 'type' => 'integer');
                $sql_data_array[] = array('fieldName' => 'entry_state', 'value' => $state, 'type' => 'string');
            }
        }

        //$sql_data_array[] = array('fieldName'=>'customers_id', 'value'=>$_SESSION['customer_id'], 'type'=>'integer');
        // TODO address_book_id已存在并且有效，则更新这个地址
        $where_clause = "address_book_id = :edit and customers_id = :customersID";
        $where_clause = $db->bindVars($where_clause, ':customersID', $_SESSION['customer_id'], 'integer');
        $where_clause = $db->bindVars($where_clause, ':edit', $address['address_book_id'], 'integer');
        $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', $where_clause);

        kancart_validate_session_default_address_id($address['address_book_id']);
        return $address;
    }
    // TODO 地址信息错误
    return false;
}

/**
 * 删除用户地址
 * @param string $customer_id
 * @return boolean
 */
function kancart_user_address_remove($address_book_id) {
    global $db;
    $delete_address_query = "DELETE from " . TABLE_ADDRESS_BOOK . "
                                WHERE address_book_id = '" . (int) $address_book_id . "'
	 			and customers_id = '" . (int) $_SESSION['customer_id'] . "' ";
    //	echo $delete_address_query;
    $db->Execute($delete_address_query);
    if ($address_book_id == $_SESSION['customer_default_address_id']) {
        $addresses = kancart_user_addresses_get();
        if ($addresses && count($addresses) > 0) {
            kancart_user_change_default_address($addresses[0]['address_book_id']);
        }
    }
    return true;
}

/**
 * 验证session 中的默认地址是否有效,如果无效则以参数中的新地址ID进行替换
 * @param string $new_address_book_id
 * @return boolean 默认地址是否有效
 */
function kancart_validate_session_default_address_id($new_address_book_id = false) {
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    if ($new_address_book_id && kancart_validate_address_id($new_address_book_id)) {
        KCLogger::Log('change user default address: ' . $new_address_book_id);
        kancart_user_change_default_address($new_address_book_id);
        return true;
    }
    KCLogger::Log('new address is invalid! ');
    if (kancart_validate_address_id($_SESSION['customer_default_address_id'])) {
        KCLogger::Log('customer_default_address_id  is valid! ');
        return true;
    } else {
        KCLogger::Log('customer_default_address_id  is invalid! ');
        return false;
    }
}

/**
 * 更改用户的默认地址
 * @param string $default_address_book_id
 * @return boolean
 */
function kancart_user_change_default_address($default_address_book_id) {
    Global $db;
    $_SESSION['customer_default_address_id'] = $default_address_book_id;
    $sql = "UPDATE " . TABLE_CUSTOMERS . "
                SET    customers_default_address_id = :customersDefaultAddressID
                WHERE  customers_id = :customersID";
    $sql = $db->bindVars($sql, ':customersDefaultAddressID', $_SESSION['customer_default_address_id'], 'integer');
    $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');

    KCLogger::Log('change user default address sql: ' . $sql);
    $db->Execute($sql);
    return true;
}

// cls_User.php