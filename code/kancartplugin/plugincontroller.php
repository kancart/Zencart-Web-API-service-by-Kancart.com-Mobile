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

date_default_timezone_set('UTC');

if (version_compare(PHP_VERSION, 5.3, '<') && function_exists('set_magic_quotes_runtime'))
    set_magic_quotes_runtime(0);
if (get_magic_quotes_gpc() > 0) {

    function kc_stripslashes_deep($value) {
        return is_array($value) ? array_map('stripslashes_deep', $value) : kc_stripslashes_deep($value);
    }

    $_POST = kc_stripslashes_deep($_POST);
    $_GET = kc_stripslashes_deep($_GET);
    $_COOKIE = kc_stripslashes_deep($_COOKIE);
}

require_once 'kancartplugin/configure.php';

require_once 'kancartplugin/tool/tool.php';
require_once 'kancartplugin/crypto/CryptoUtil.php';

require_once 'kancartplugin/KC_Response.php';

$kcResponse = KC_Response::getInstance();

$_POST['app_key'] = CryptoUtil::Crypto($_POST['app_key'], 'AES-256', KANCART_APP_SECRECT, false);


if (isset($_SESSION['ALLOW_UPGRADE']) && $_SESSION['ALLOW_UPGRADE'] === true) {
    api_Upgrade();
    die();
}


if (!isset($_POST['app_key']) || $_POST['app_key'] != KANCART_APP_KEY) {
    die('KanCart OpenAPI v1.1 is installed. Zencart Plugin ' . ZENCART_PLUGIN_VERSION);
}

if (!isset($_POST['v']) || $_POST['v'] != '1.1') {
    $kcResponse->ErrorBack('0x0005');
}

if (!isset($_POST['timestamp']) || abs(time() - strtotime($_POST['timestamp'])) > 1800) {
    $kcResponse->ErrorBack('0x0003');
}

if (!isset($_POST['session_id'])) {
    $kcResponse->ErrorBack('0x0017');
}

if (!isset($_POST['sign_method']) || $_POST['sign_method'] != 'md5') {
    $kcResponse->ErrorBack('0x0006');
}

if (!isset($_POST['sign']) || !validateRequestSign($_POST)) {
    $kcResponse->ErrorBack('0x0016');
}

if (isset($_POST['format']) && $_POST['format'] == KC_Response::$FORMAT_XML) {
    $kcResponse->responseFormat = KC_Response::$FORMAT_XML;
}

if (!isset($_POST['method'])) {
    $kcResponse->ErrorBack('0x0018');
}

if (!isset($_POST['language'])) {
    $kcResponse->ErrorBack('0x0007');
} else {
    $_POST['language'] = strtolower($_POST['language']);
}

$lng = new language();
if (isset($_POST['language']) && zen_not_null($_POST['language'])) {
    $lng->set_language($_POST['language']);
} else {
    if (LANGUAGE_DEFAULT_SELECTOR == 'Browser') {
        $lng->get_browser_language();
    } else {
        $lng->set_language(DEFAULT_LANGUAGE);
    }
}
$_SESSION['language'] = (zen_not_null($lng->language['directory']) ? $lng->language['directory'] : 'english');
$_SESSION['languages_id'] = (zen_not_null($lng->language['id']) ? $lng->language['id'] : 1);
$_SESSION['languages_code'] = (zen_not_null($lng->language['code']) ? $lng->language['code'] : 'en');

require_once DIR_WS_LANGUAGES . $_SESSION['language'] . '.php';
if (SESSION_IP_TO_HOST_ADDRESS == 'true') {
    $email_host_address = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
} else {
    $email_host_address = OFFICE_IP_TO_HOST_ADDRESS;
}
$_SESSION['customers_host_address'] = $email_host_address;

if (!isset($_POST['currency'])) {
    $_POST['currency'] = DEFAULT_CURRENCY;
} else {
    $_POST['currency'] = strtoupper($_POST['currency']);
}

$currencies = new currencies();
if (!array_key_exists($_POST['currency'], $currencies->currencies)) {
    $_POST['currency'] = DEFAULT_CURRENCY;
}
$_SESSION['currency'] = $_POST['currency'];


switch ($_POST['method']) {
    case 'KanCart.User.Login' : api_User_Login();
        break;
    case 'KanCart.User.Get' : api_User_Get();
        break;
    case 'KanCart.User.Register' : api_User_Register();
        break;
    case 'KanCart.User.Update' : api_User_Update();
        break;
    case 'KanCart.User.Logout' : api_User_Logout();
        break;
    case 'KanCart.User.IsExists' : api_User_IsExists();
        break;
    case 'KanCart.User.Address.Update' : api_User_Address_Update();
        break;
    case 'KanCart.User.Address.Remove' : api_User_Address_Remove();
        break;
    case 'KanCart.User.Address.Add' : api_User_Address_Add();
        break;
    case 'KanCart.User.Address.Get' : api_User_Address_Get();
        break;
    case 'KanCart.User.Addresses.Get' : api_User_Addresses_Get();
        break;
    case 'KanCart.Countries.Get' : api_Countries_Get();
        break;
    case 'KanCart.Zones.Get' : api_Zones_Get();
        break;
    case 'KanCart.Currencies.Get' : api_Currencies_Get();
        break;
    case 'KanCart.Languages.Get' : api_Languages_Get();
        break;
    case 'KanCart.Category.Get' : api_Category_Get();
        break;
    case 'KanCart.Item.Get' : api_Item_Get();
        break;
    case 'KanCart.Items.Get' : api_Items_Get();
        break;
    case 'KanCart.ShoppingCart.Add' : api_ShoppingCart_Add();
        break;
    case 'KanCart.ShoppingCart.Remove' : api_ShoppingCart_Remove();
        break;
    case 'KanCart.ShoppingCart.Get' : api_ShoppingCart_Get();
        break;
    case 'KanCart.ShoppingCart.Update' : api_ShoppingCart_Update();
        break;
    case 'KanCart.ShoppingCart.Checkout' : api_ShoppingCart_Checkout();
        break;
    case 'KanCart.ShoppingCart.PayPalEC.Start' : api_ShoppingCart_PayPalEC_Start();
        break;
    case 'KanCart.ShoppingCart.PayPalEC.Detail' : api_ShoppingCart_PayPalEC_Detail();
        break;
    case 'KanCart.ShoppingCart.PayPalEC.Pay' : api_ShoppingCart_PayPalEC_Pay();
        break;
    case 'KanCart.ShoppingCart.PayPalWPS.Done' : api_ShoppingCart_PayPalWPS_Done();
        break;
    case 'KanCart.ShoppingCart.Checkout.Detail' : api_ShoppingCart_Checkout_Detail();
        break;
    case 'KanCart.ShoppingCart.Addresses.Update' : api_ShoppingCart_Addresses_Update();
        break;
    case 'KanCart.ShoppingCart.ShippingMethods.Update' : api_ShoppingCart_ShippingMethods_Update();
        break;
    case 'KanCart.ShoppingCart.Coupons.Update' : api_ShoppingCart_Coupons_Update();
        break;
    case 'KanCart.Orders.Count' : api_orders_count();
        break;
    case 'KanCart.Orders.Get' : api_Orders_Get();
        break;
    case 'KanCart.OrderStatuses.Get' : api_OrderStatuses_Get();
        break;
    case 'KanCart.Order.Get' : api_Order_Get();
        break;
    case 'KanCart.Order.Checkout' : api_Order_Checkout();
        break;
    case 'KanCart.Order.PayPalEC.Pay' : api_Order_PayPalEC_Pay();
        break;
    case 'KanCart.TradeRates.Get' : api_TradeRates_Get();
        break;
    case 'KanCart.TradeRate.Add' : api_TradeRate_Add();
        break;
    case 'KanCart.Keywords.Get' : api_Keywords_Get();
        break;
    case 'KanCart.Plugin.Upgrade': api_Upgrade();
        break;
    default: $kcResponse->ErrorBack('0x0018');
        return;
}

/**
 * 用户登录
 */
function api_User_Login() {
    Global $kcResponse;
    require_once 'kancartplugin/class/cls_User.php';

    if (!isset($_POST['uname']) && !isset($_POST['pwd'])) {
        $kcResponse->ErrorBack('0x1007');
    }

    $email_address = $_POST['uname'];
    $password = $_POST['pwd'];
    $plainPassword = CryptoUtil::Crypto($password, 'AES-256', KANCART_APP_SECRECT, false);
    kancart_user_login($email_address, $plainPassword);
}

/**
 * 用户登出
 */
function api_User_Logout() {
    Global $kcResponse;
    require_once 'kancartplugin/class/cls_User.php';
    $customer_id = $_SESSION['customer_id'];
    if (!isEmptyString($customer_id) && kancart_user_logout()) {
        $kcResponse->DataBack(array());
    } else {
        $kcResponse->DataBack(array());
    }
}

/**
 *  获取用户信息
 */
function api_User_Get() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_User.php';
        $email_address = $_POST['uname'];
        $fields = explode(',', $_POST['fields']);
        $kcResponse->DataBack(array('user' => kancart_user_get($email_address, $fields)));
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 *  用户更新
 */
function api_User_Update() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_User.php';

        $user = new User();
        if (isset($_POST['uname'])) {
            $user->uname = $_POST['uname'];
        }
        if (isset($_POST['pwd'])) {
            $user->pwd = $_POST['pwd'];
        }
        if (isset($_POST['email'])) {
            $user->email = $_POST['uname'];
        }
        if (isset($_POST['nick'])) {
            $user->nick = $_POST['nick'];
        }
        if (isset($_POST['fax'])) {
            $user->fax = $_POST['fax'];
        }
        if (isset($_POST['mobile'])) {
            $user->mobile = $_POST['mobile'];
        }
        if (isset($_POST['telephone'])) {
            $user->telephone = $_POST['telephone'];
        }
        if (isset($_POST['dob'])) {
            $user->dob = $_POST['dob'];
        }
        if (isset($_POST['lastname'])) {
            $user->lastname = $_POST['lastname'];
        }
        if (isset($_POST['firstname'])) {
            $user->firstname = $_POST['firstname'];
        }
        if (isset($_POST['gender'])) {
            $user->gender = $_POST['gender'];
        }
        if (kancart_userupdate($user)) {
            $kcResponse->DataBack(array());
        } else {
            $kcResponse->ErrorBack('0x1001');
        }
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 *  用户注册
 */
function api_User_Register() {
    Global $kcResponse;
    if (!isset($_POST['uname']) || !isset($_POST['pwd'])) {
        $kcResponse->DataBack('0x1001');
    }
    require_once 'kancartplugin/class/cls_User.php';
    if (kancart_user_isExists($_POST['uname'])) {
        $kcResponse->DataBack('0x1009');
    }

    $user = new KC_User();
    if (isset($_POST['uname'])) {
        $user->uname = $_POST['uname'];
    }
    if (isset($_POST['pwd'])) {
        $user->pwd = CryptoUtil::Crypto($_POST['pwd'], 'AES-256', KANCART_APP_SECRECT, false);
    }
    if (isset($_POST['email'])) {
        $user->email = $_POST['email'];
    }
    if (isset($_POST['nick'])) {
        $user->nick = $_POST['nick'];
    }
    if (isset($_POST['fax'])) {
        $user->fax = $_POST['fax'];
    }
    if (isset($_POST['mobile'])) {
        $user->mobile = $_POST['mobile'];
    }
    if (isset($_POST['telephone'])) {
        $user->telephone = $_POST['telephone'];
    }
    if (isset($_POST['dob'])) {
        $user->dob = $_POST['dob'];
    }
    if (isset($_POST['lastname'])) {
        $user->lastname = $_POST['lastname'];
    }
    if (isset($_POST['firstname'])) {
        $user->firstname = $_POST['firstname'];
    }
    if (isset($_POST['gender'])) {
        $user->gender = $_POST['gender'];
    }
    $result = kancart_user_register($user);
    if ($result) {
        $kcResponse->DataBack(array());
    } else {
        $kcResponse->ErrorBack('0x1001');
    }
}

/**
 * 用户是否存在
 */
function api_User_IsExists() {
    Global $kcResponse;
    if (!isset($_POST['uname'])) {
        $kcResponse->ErrorBack('0x1007');
    }
    require_once 'kancartplugin/class/cls_User.php';
    $name = $_POST['uname'];
    $result = kancart_user_isExists($name);
    $back = array();
    if ($result) {
        $back['uname_is_exist'] = "true";
        $back['nick_is_exist'] = "false";
    } else {
        $back['uname_is_exist'] = "false";
        $back['nick_is_exist'] = "false";
    }
    $kcResponse->DataBack($back);
}

/**
 * 用户地址新增
 */
function api_User_Address_Add() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_User.php';
        $address = array();
        if (isset($_POST['address_book_id'])) {
            $address['address_book_id'] = $_POST['address_book_id'];
        }
        if (isset($_POST['gender'])) {
            $address['gender'] = $_POST['gender'];
        }
        if (isset($_POST['firstname'])) {
            $address['firstname'] = $_POST['firstname'];
        }
        if (isset($_POST['lastname'])) {
            $address['lastname'] = $_POST['lastname'];
        }
        if (isset($_POST['address1'])) {
            $address['address1'] = $_POST['address1'];
        }
        if (isset($_POST['address2'])) {
            $address['address2'] = $_POST['address2'];
        }
        if (isset($_POST['city'])) {
            $address['city'] = $_POST['city'];
        }
        if (isset($_POST['postcode'])) {
            $address['postcode'] = $_POST['postcode'];
        }
        if (isset($_POST['company'])) {
            $address['company'] = $_POST['company'];
        }
        if (isset($_POST['telephone'])) {
            $address['telephone'] = $_POST['telephone'];
        } else {
            $address['telephone'] = '';
        }
        if (ACCOUNT_STATE == 'true' || ACCOUNT_STATE == 'false') {
            $address['state'] = (isset($_POST['state'])) ? zen_db_prepare_input($_POST['state']) : zen_db_prepare_input($_POST['zone_code']);

            if (isset($_POST['zone_id'])) {
                $address['zone_id'] = zen_db_prepare_input($_POST['zone_id']);
            } else {
                $address['zone_id'] = false;
            }
        }

        $address['country_id'] = zen_db_prepare_input($_POST['country_id']);
        if (kancart_user_address_add($address)) {
            $kcResponse->DataBack(array());
        } else {
            $kcResponse->ErrorBack('0x1001');
        }
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 *  用户地址修改
 */
function api_User_Address_Update() {
    Global $kcResponse;
    if (hasLogin()) {
        if (!isset($_POST['address_book_id'])) {
            $kcResponse->ErrorBack('0x1001');
        }
        require_once 'kancartplugin/class/cls_User.php';
        $address = array();
        if (isset($_POST['address_book_id'])) {
            $address['address_book_id'] = $_POST['address_book_id'];
        }
        if (isset($_POST['gender'])) {
            $address['gender'] = $_POST['gender'];
        }
        if (isset($_POST['firstname'])) {
            $address['firstname'] = $_POST['firstname'];
        }
        if (isset($_POST['lastname'])) {
            $address['lastname'] = $_POST['lastname'];
        }
        if (isset($_POST['address1'])) {
            $address['address1'] = $_POST['address1'];
        }
        if (isset($_POST['address2'])) {
            $address['address2'] = $_POST['address2'];
        }
        if (isset($_POST['city'])) {
            $address['city'] = $_POST['city'];
        }
        if (isset($_POST['postcode'])) {
            $address['postcode'] = $_POST['postcode'];
        }
        if (isset($_POST['company'])) {
            $address['company'] = $_POST['company'];
        }
        if (isset($_POST['telephone'])) {
            $address['telephone'] = $_POST['telephone'];
        } else {
            $address['telephone'] = '';
        }

        if (ACCOUNT_STATE == 'true' || ACCOUNT_STATE == 'false') {
            $address['state'] = (isset($_POST['state'])) ? zen_db_prepare_input($_POST['state']) : zen_db_prepare_input($_POST['zone_code']);
            if (isset($_POST['zone_id'])) {
                $address['zone_id'] = zen_db_prepare_input($_POST['zone_id']);
            } else {
                $address['zone_id'] = false;
            }
        }

        $address['country_id'] = zen_db_prepare_input($_POST['country_id']);

        if (kancart_user_address_update($address)) {
            $kcResponse->DataBack(array());
        } else {
            $kcResponse->ErrorBack('0x1001');
        }
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 *  用户地址删除
 */
function api_User_Address_Remove() {
    if (hasLogin()) {
        if (!isset($_POST['address_book_id'])) {
            $kcResponse->ErrorBack('0x1001');
        }
        require_once 'kancartplugin/class/cls_User.php';
        if (kancart_user_address_remove($_POST['address_book_id'])) {
            $kcResponse->DataBack(array());
        } else {
            $kcResponse->ErrorBack('0x1001');
        }
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 *  获取用户地址
 */
function api_User_Address_Get() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_User.php';
        $back = array();
        $addressid = $_POST['address_book_id'];
        $field = explode(',', $_POST['fields']);
        $result = kancart_user_address_get($_POST['address_book_id']);
        if ($result) {
            $back["address"] = $result;
            $kcResponse->DataBack($back);
        } else {
            $kcResponse->ErrorBack('0x1001', 'This address may have been deleted.');
        }
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 * 获取用户的地址列表
 */
function api_User_Addresses_Get() {
    Global $kcResponse;
    if (hasLogin()) {
        if (!isset($_POST['fields'])) {
            $kcResponse->ErrorBack('0x0001');
        }
        require_once 'kancartplugin/class/cls_User.php';

        $back = array();
        $email_address = $_POST['uname'];

        $address_type = '';
        if (isset($_POST['address_type'])) {
            $address_type = $_POST['address_type'];
        }
        $field = explode(',', $_POST['fields']);
        $customer_id = $_SESSION['customer_id'];

        $back["addresses"] = kancart_user_addresses_get($customer_id, $address_type, $field);
        $kcResponse->DataBack($back);
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 *  获取国家列表
 */
function api_Countries_Get() {
    require_once 'kancartplugin/class/cls_Country.php';
    kancart_countries_get();
}

/**
 *  获取区域列表
 */
function api_Zones_Get() {
    require_once 'kancartplugin/class/cls_Zone.php';
    kancart_zones_get();
}

/**
 * 获取支持的货币
 */
function api_Currencies_Get() {
    require_once 'kancartplugin/class/cls_Currency.php';
    kancart_currencies_get();
}

/**
 * 获取支持的语言
 */
function api_Languages_Get() {
    require_once 'kancartplugin/class/cls_Language.php';
    kancart_languages_get();
}

/**
 *  获取商品类目
 */
function api_Category_Get() {
    Global $kcResponse;
    require_once 'kancartplugin/class/cls_Category.php';
    $fields = explode(',', $_POST['fields']);
    if (isset($_POST['all_cat']) && $_POST['all_cat'] == true) {
        kancart_categories_all($fields);
    } else {
        if (!isset($_POST['parent_cid'])) {
            $kcResponse->ErrorBack('0x2002');
        }
        $parent_cid = 0;
        if ($_POST['parent_cid'] != -1) {
            $parent_cid = $_POST['parent_cid'];
        }
        kancart_categories_get($parent_cid, $fields);
    }
}

/**
 *  获取商品信息
 */
function api_Item_Get() {
    Global $kcResponse;
    if (!isset($_POST['item_id']) || isEmptyString($_POST['item_id'])) {
        $kcResponse->ErrorBack('0x3002');
    }
    require_once 'kancartplugin/class/cls_Item.php';
    kancart_item_get($_POST['item_id']);
}

/**
 * 获取商品信息列表
 */
function api_Items_Get() {
    Global $kcResponse;
    $cid = 0;
    $query = '';
    $special = '';
    $currencycode = '';
    $order_by = '';
    $page_no = 1;
    $page_size = 20;

    $post_free = false;

    $start_price = '';
    $end_price = '';

    $start_score = 5;
    $end_score = 5;

    if (!isset($_POST['query']) && !isset($_POST['cid']) && !isset($_POST['special'])) {
        $kcResponse->ErrorBack('0x3002');
    }
    if (isset($_POST['currency'])) {
        $currencycode = $_POST['currency'];
    }
    if (isset($_POST['special'])) {
        $special = $_POST['special'];
    }
    if (isset($_POST['query'])) {
        $query = $_POST['query'];
    }
    if (isset($_POST['cid'])) {
        $cid = $_POST['cid'];
    }
    if (isset($_POST['order_by'])) {
        $order_by = $_POST['order_by'];
    }
    if (isset($_POST['page_no'])) {
        $page_no = $_POST['page_no'];
    }
    if (isset($_POST['page_size'])) {
        $page_size = $_POST['page_size'];
    }
    if (isset($_POST['post_free'])) {
        $post_free = $_POST['post_free'];
    }
    if (isset($_POST['start_score'])) {
        $start_score = $_POST['start_score'];
    }
    if (isset($_POST['end_score'])) {
        $end_score = $_POST['end_score'];
    }
    if (isset($_POST['start_price'])) {
        $start_price = $_POST['start_price'];
    }
    if (isset($_POST['end_price'])) {
        $end_price = $_POST['end_price'];
    }

    require_once 'kancartplugin/class/cls_Item.php';
    require_once 'kancartplugin/class/cls_Category.php';

    $fields = explode(',', $_POST['fields']);

    $returnResult = kancart_items_get();
    $returnResult['item_cats'] = array();

    $kcResponse->DataBack($returnResult);
}

/**
 * 添加商品到购物车
 */
function api_ShoppingCart_Add() {
    Global $kcResponse;
    KCLogger::Log('get cart session id: ' . session_id());
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    if (!isset($_POST['item_id'])) {
        // item_id 错误
        $kcResponse->ErrorBack('0x3002');
    }
    $kcResponse->DataBack(kancart_shoppingcart_add());
}

/**
 * 从购物车移除商品
 */
function api_ShoppingCart_Remove() {
    Global $kcResponse;
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    if (!isset($_POST['cart_item_id'])) {
        // cart_item_id 错误
        $kcResponse->ErrorBack('0x3002');
    }
    $kcResponse->DataBack(kancart_shoppingcart_remove());
}

/**
 * 添加购物车内所有商品
 */
function api_ShoppingCart_Get() {
    Global $kcResponse;
    KCLogger::Log('get cart session id: ' . session_id());
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    $kcResponse->DataBack(kancart_shoppingcart_get());
}

/**
 * 更新购物车商品数量
 */
function api_ShoppingCart_Update() {
    Global $kcResponse;
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    $kcResponse->DataBack(kancart_shoppingcart_update());
}

/**
 * 生成订单
 */
function api_ShoppingCart_Checkout() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_ShoppingCart.php';
        $kcResponse->DataBack(kancart_shoppingcart_checkout());
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

function api_ShoppingCart_Checkout_Detail() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_ShoppingCart.php';
        $kcResponse->DataBack(kancart_shoppingcart_checkout_detail());
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 * 结算时修改账单地址和送货地址
 */
function api_ShoppingCart_Addresses_Update() {
    Global $kcResponse;
//    if (hasLogin()) {
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    $kcResponse->DataBack(kancart_shoppingcart_address_update());
//    } else {
//        $kcResponse->ErrorBack('0x0002');
//    }
}

/**
 * 结算时修改运输方式
 */
function api_ShoppingCart_ShippingMethods_Update() {
    Global $kcResponse;
//    if (hasLogin()) {
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    $kcResponse->DataBack(kancart_shoppingcart_shippingmethods_update());
//    } else {
//        $kcResponse->ErrorBack('0x0002');
//    }
}

/**
 * 结算时修改使用的coupon
 */
function api_ShoppingCart_Coupons_Update() {
    Global $kcResponse;
//    if (hasLogin()) {
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    $kcResponse->DataBack(kancart_shoppingcart_coupons_update());
//    } else {
//        $kcResponse->ErrorBack('0x0002');
//    }
}

/**
 * 获取需要显示的相应状态的订单数量
 */
function api_Orders_Count() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_Order.php';
        kancart_orders_count();
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 * 获取订单列表
 */
function api_Orders_Get() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_Order.php';
        kancart_orders_get();
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 * 获取订单的所有可能状态
 */
function api_OrderStatuses_Get() {
    require_once 'kancartplugin/class/cls_OrderStatus.php';
    kancart_orderstatuses_get();
}

/**
 *  获取单个订单
 */
function api_Order_Get() {
    Global $kcResponse;
    if (hasLogin()) {
        if (!isset($_POST['order_id'])) {
            $kcResponse->ErrorBack('0x6002');
        }
        require_once 'kancartplugin/class/cls_Order.php';
        kancart_order_get();
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 *  订单支付
 */
function api_Order_Checkout() {
    Global $kcResponse, $order, $order_totals, $db, $paypalwpp, $order_total_modules;
    require_once DIR_WS_CLASSES . 'order.php';
    $order = new order($_POST['order_id']);
    require_once DIR_WS_CLASSES . 'order_total.php';
    $order_total_modules = new order_total;
    $order_totals = $order_total_modules->pre_confirmation_check();
    $order_totals = $order_total_modules->process();
    if ($_POST['payment_method_id'] == 'paypalwpp') {
        $kcResponse->DataBack(array('paypal_redirect_url'=>'http://www.sandbox.paypal.com'));
    } else if ($_POST['payment_method_id'] == 'paypal') {
        $returnResults = kancart_shoppingcart_paypalwps_start();
    }
}

function api_Order_PayPalEC_Pay() {
    Global $kcResponse;
    $kcResponse->DataBack();
}

/**
 * 获取商品评价
 */
function api_TradeRates_Get() {
    Global $kcResponse;
    require_once 'kancartplugin/class/cls_TradeRate.php';
    $kcResponse->DataBack(kancart_traderates_get());
}

/**
 *  新增商品评价
 */
function api_TradeRate_Add() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_TradeRate.php';
        $kcResponse->DataBack(kancart_traderate_add());
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

/**
 *  关键字查找
 */
function api_Keywords_Get() {
    // TODO 获取关键字
}

/**
 *  自动升级函数 
 */
function api_Upgrade() {
    $_SESSION['ALLOW_UPGRADE'] = true;
    require 'kancartplugin/upgrade/upgrade.php';
}

/**
 * 开始一个 PayPal ExpressCheckout 支付请求
 */
function api_ShoppingCart_PayPalEC_Start() {
    Global $kcResponse;
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    $kcResponse->DataBack(kancart_shoppingcart_paypalec_start('continue'));
}

/**
 * 获取当前 PayPal ExpressCheckout 支付的详情
 */
function api_ShoppingCart_PayPalEC_Detail() {
    Global $kcResponse;
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    $kcResponse->DataBack(kancart_shoppingcart_paypalec_detail());
}

function api_ShoppingCart_PayPalEC_Pay() {
    Global $kcResponse;
//    if (hasLogin()) {
    require_once 'kancartplugin/class/cls_ShoppingCart.php';
    $kcResponse->DataBack(kancart_shoppingcart_paypalec_pay());
//    } else {
//        $kcResponse->ErrorBack('0x0002');
//    }
}

function api_ShoppingCart_PayPalWPS_Done() {
    Global $kcResponse;
    if (hasLogin()) {
        require_once 'kancartplugin/class/cls_ShoppingCart.php';
        $kcResponse->DataBack(kancart_shoppingcart_paypalwps_done());
    } else {
        $kcResponse->ErrorBack('0x0002');
    }
}

include_once '../includes/application_bottom.php';

function hasLogin() {
    if (isVaildSessionKey() && !isLoginExpired()) {
        $_POST['uname'] = $_SESSION['kancart_login_uname'];
        return true;
    }
    return false;
}

function isVaildSessionKey() {
    if (isset($_SESSION['kancart_session_key'])
            && strlen($_SESSION['kancart_session_key']) > 20
            && $_SESSION['kancart_session_key'] == $_POST['session']) {
        return true;
    }
    return false;
}

function isLoginExpired() {
    if (isset($_SESSION['kancart_last_login_date']) && $_SESSION['kancart_last_login_date'] > (time() - 60 * 60)) {
        return false;
    }
    return true;
}

function validateRequestSign(array $requestParams) {
    if (!isset($requestParams['sign']) || isEmptyString($requestParams['sign'])) {
        return false;
    }
    $sign = $requestParams['sign'];
    unset($requestParams['sign']);
    ksort($requestParams);
    reset($requestParams);
    $tempStr = "";
    foreach ($requestParams as $key => $value) {
        $tempStr = $tempStr . $key . $value;
    }
    $tempStr = $tempStr . KANCART_APP_SECRECT;
    return strtoupper(md5($tempStr)) === $sign;
}

// server.php end