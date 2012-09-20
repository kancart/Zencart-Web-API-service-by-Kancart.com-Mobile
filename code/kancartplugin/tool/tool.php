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

function object2array($value) {
   if (!(is_array($value) || is_object($value))) {
       return $value;
   }
   $array = array();
   foreach ($value as $key => $row) {
       $array[$key] = object2array($row);
   }
   return $array;
}

if (!function_exists("json_encode")) {
   include_once("kancartplugin/tool/JSON.php");
   function json_encode($array) {
       $json = new Services_JSON();
       $json_array = $json->encode($array);
       return $json_array;
   }
   function json_decode($json_data, $toarray = TRUE) {
       $json = new Services_JSON();
       $array = $json->decode($json_data);
       if ($toarray) {
           $array = object2array($array);
       }
       return $array;
   }
}

function addslashes_deep($value) {
    if (empty($value)) {
        return $value;
    } else {
        return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
    }
}

function addslashes_deep_obj($obj) {
    if (is_object($obj) == true) {
        foreach ($obj AS $key => $val) {
            $obj->$key = addslashes_deep($val);
        }
    } else {
        $obj = addslashes_deep($obj);
    }

    return $obj;
}

function isEmptyString($string) {

    if (!is_string($string)) {
        return true;
    }

    if (empty($string)) {
        return true;
    }

    if ($string == '') {
        return true;
    }
    return false;
}

?>