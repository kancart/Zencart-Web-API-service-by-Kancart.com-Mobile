<?php

if (!defined('ALLOW')) {
 header('HTTP/1.1 404 Not Found');
 die();
}
 

define('ZENCART_PLUGIN_VERSION', 'v1.2.3');

//Replace those keys with real value.
define('KANCART_APP_KEY', 'KC_APP_KEY');
define('KANCART_APP_SECRECT' , 'KC_APP_SECRET');
 
 
define('ADDRESS_WITH_PHONE', false);
 
define('ADDRESS_PHONE_KEY', 'entry_phone');
 
define('REVIEW_WITH_TITLE', false);
 
define('REVIEW_TITLE_KEY', 'reviews_title');
 
 
// configure.php end
?>