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

class KC_Response {

    private static $instance = NULL;
    public static $FORMAT_JSON = 'json';
    public static $FORMAT_XML = 'xml';
    public $responseFormat = '';
    private $errArray = array();

    public static function getInstance() {

        if (is_null(self::$instance) || !isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->responseFormat = KC_Response::$FORMAT_JSON;
        }

        return self::$instance;
    }

    private function __construct() {
        $err_arr = array();

        $err_arr['0x0001'] = 'Invalid API (System)';
        $err_arr['0x0002'] = 'Invalid AppKey (System)';
        $err_arr['0x0003'] = 'Time error over 10min (System)';
        $err_arr['0x0004'] = 'Invalid response format (System)';
        $err_arr['0x0005'] = 'Invalid API version (System)';
        $err_arr['0x0006'] = 'Invalid encryption method (System)';
        $err_arr['0x0007'] = 'Language is not supported (System)';
        $err_arr['0x0008'] = 'Currency is not supported (System)';
        $err_arr['0x0009'] = 'Authentication failed (System)';
        $err_arr['0x0010'] = 'Time out (System)';
        $err_arr['0x0011'] = 'Data error (System)';
        $err_arr['0x0012'] = 'DataBase error (System)';
        $err_arr['0x0013'] = 'Server error (System)';
        $err_arr['0x0014'] = 'Permission denied (System)';
        $err_arr['0x0015'] = 'Service unavailable (System)';
        $err_arr['0x0016'] = 'Invalid signature (System)';
        $err_arr['0x0017'] = 'Invalid device ID (System)';
        $err_arr['0x0018'] = 'Invalid method (System)';

        $err_arr['0x1001'] = 'User does not exist (User)';
        $err_arr['0x1002'] = 'Password error (User)';
        $err_arr['0x1003'] = 'Verification code error (User)';
        $err_arr['0x1004'] = 'Invalid AddressID (User)';
        $err_arr['0x1005'] = 'Invalid return fields (User)';
        $err_arr['0x1006'] = 'No information in the region (User)';
        $err_arr['0x1007'] = 'Input parameter error (User)';
        $err_arr['0x1008'] = 'This account is banned (User)';
        $err_arr['0x1009'] = 'Input parameter error (User)';

        $err_arr['0x2001'] = 'Invalid return fields (Category)';
        $err_arr['0x2002'] = 'Input parameter error (Category)';
        $err_arr['0x2003'] = 'No subcategory in it. (Category)';

        $err_arr['0x3001'] = 'Invalid return fields (Item)';
        $err_arr['0x3002'] = 'Input parameter error (Item)';
        $err_arr['0x3003'] = 'Item does not exists (Item)';

        $err_arr['0x4001'] = 'Invalid return fields (Postage)';
        $err_arr['0x4002'] = 'Input parameter error (Postage)';

        $err_arr['0x5001'] = 'Invalid ItemID (Cart)';
        $err_arr['0x5002'] = 'Input parameter error (Cart)';

        $err_arr['0x6001'] = 'Invalid return fields (Order)';
        $err_arr['0x6002'] = 'Invalid OrderID (Order)';
        $err_arr['0x6003'] = 'Input parameter error (Order)';

        $err_arr['0x7001'] = 'User does not exist (Favorites)';
        $err_arr['0x7002'] = 'Invalid ItemID (Favorites)';

        $err_arr['0x8001'] = 'Invalid return fields (Rating)';
        $err_arr['0x8002'] = 'Invalid ItemID (Rating)';
        $err_arr['0x8003'] = 'Input parameter error (Rating)';
        $err_arr['0x8004'] = 'User does not exist (Rating)';

        $err_arr['0x9001'] = '';
    }

    public function DataBack($data) {
        $kcData = new KC_Data('0x0000', 'success', $data);
        die($kcData->outputString($this->responseFormat));
    }

    public function ErrorBack($code, $errMsg = false) {
        // 如果未设置自定义错误消息($err_msg)，那么根据错误代码来获取预定义的错误消息
        if (isset($this->errArray[$code]) && !$errMsg) {
            $errMsg = $this->errArray[$code];
        }
        $info = array('err_msg' => $errMsg);
        $kcData = new KC_Data($code, 'fail', $info);
        KCLogger::Log('$this->responseFormat: ' . $this->responseFormat);
        // 输出数据并结束请求
        //$this->logRequest();
        die($kcData->outputString($this->responseFormat));
    }

}

class KC_Data {

    private $code = '0x0000';
    private $result = 'success';
    private $info = null;

    function __construct($code, $result, $info) {
        $this->code = $code;
        $this->result = $result;
        $this->info = $info;
    }

    public function getCode() {
        return $this->code;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function getResult() {
        return $this->result;
    }

    public function setResult($result) {
        $this->result = $result;
    }

    public function getInfo() {
        return $this->info;
    }

    public function setInfo($info) {
        $this->info = $info;
    }

    /**
     * 获得输出字符串
     * @return string
     */
    public function outputString($format) {
        switch ($format) {
            case KC_Response::$FORMAT_JSON: {
                    $returnResult = array();
                    $returnResult['result'] = $this->result;
                    $returnResult['code'] = $this->code;
                    if (!$this->info) {
                        $returnResult['info'] = new KC_EmptyClass();
                    } else {
                        $returnResult['info'] = $this->info;
                    }
                    return json_encode($returnResult);
                }
            case KC_Response::$FORMAT_XML:
		// not implement
                return "";
            default:return "";
        }
    }

}

class KC_EmptyClass {
    
}

// KC_Response.php end