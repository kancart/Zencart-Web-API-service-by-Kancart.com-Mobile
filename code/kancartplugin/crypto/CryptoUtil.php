<?php

/**
 * KanCart
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http:
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@kancart.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade KanCart to newer
 * versions in the future. If you wish to customize KanCart for your
 * needs please refer to http:
 *
 * @copyright  Copyright (c) 2011 kancart.com (http:
 * @license    http:
 */
if (!defined('ALLOW')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

/**
 * The none padding string consists of nothing.
 */
define('CRYPT_PADDING_MODE_NONE', -1);

/**
 * The zero padding string consists of a sequence of bytes filled with zeros.
 */
define('CRYPT_PADDING_MODE_ZERO', 0);

/**
 * The ansix923 padding string consists of 
 * a sequence of bytes filled with zeros before the length 
 */
define('CRYPT_PADDING_MODE_ANSIX923', 1);

/**
 * The iso10126 padding string consists of random data before the length.
 */
define('CRYPT_PADDING_MODE_ISO10126', 2);

/**
 * The pkcs 7 padding string consists of a sequence of bytes, 
 * each of which is equal to the total number of padding bytes added.
 */
define('CRYPT_PADDING_MODE_PKCS7', 3);

class CryptoUtil {

    public static function Crypto($text, $cipher, $key, $isEncrypt) {
        switch ($cipher) {
            case 'DES': {
                    require_once 'kancartplugin/crypto/DES.php';
                    $crypt = new Crypt_DES(CRYPT_DES_MODE_CBC);
                    $crypt->setKey($key);
                    $crypt->setIV($key);
                    if ($isEncrypt) {
                        return strtoupper(bin2hex($crypt->encrypt($text)));
                    } else {
                        return $crypt->decrypt(CryptoUtil::hex2bin($text));
                    }
                } break;
            case 'AES-256': {
                    require_once 'kancartplugin/crypto/Rijndael.php';
                    $crypt = new Crypt_Rijndael(CRYPT_RIJNDAEL_MODE_ECB);
                    $crypt->setKey($key);
                    if ($isEncrypt) {
                        return strtoupper(bin2hex($crypt->encrypt($text)));
                    } else {
                        return $crypt->decrypt(CryptoUtil::hex2bin($text));
                    }
                } break;
            default:break;
        }
        return "ERROR";
    }

    private static function hex2bin($hexData) {
        $binData = "";
        for ($i = 0; $i < strlen($hexData); $i += 2) {
            $binData .= chr(hexdec(substr($hexData, $i, 2)));
        }
        return $binData;
    }

}

// CryptoUtil.php end