<?php
if (!defined('ALLOW')) {
    header('HTTP/1.1 404 Not Found');
    die();
}
/*
 * constants.php
 * PayPal includes the following API Signature for making API
 * calls to the PayPal sandbox:
 * API Username     sdk-three_api1.sdk.com
 * API Password     QFZCWN5HZM8VBG7Q
 * API Signature    A.d9eRKfd1yVkRrtmMfCFLTqa6M9AyodL0SJkhYztxUi8W9pCXF6.4NI
 */
//for 3-token -> API_USERNAME,API_PASSWORD,API_SIGNATURE  are needed

define('API_ENDPOINT', 'https://api-3t.paypal.com/nvp'); // live
define('API_ENDPOINT_SANDBOX', 'https://api-3t.sandbox.paypal.com/nvp'); // sandbox

define('SUBJECT', '');
/* for permission APIs ->token, signature, timestamp  are needed
  define('AUTH_TOKEN',"4oSymRbHLgXZVIvtZuQziRVVxcxaiRpOeOEmQw");
  define('AUTH_SIGNATURE',"+q1PggENX0u+6vj+49tLiw9CLpA=");
  define('AUTH_TIMESTAMP',"1284959128");
 */

define('USE_PROXY', false);
define('PROXY_HOST', '127.0.0.1');
define('PROXY_PORT', '8888');

/**
 * For the sandbox, the URL is
 * https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
 * For the live site, the URL is
 * https://www.paypal.com/webscr&cmd=_express-checkout&token=
 */
define('PAYPAL_URL', 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=');

/**
 * # The only supported value at this time is 2.3
 */
define('VERSION', '65.1');

// Ack related constants
define('ACK_SUCCESS', 'SUCCESS');
define('ACK_SUCCESS_WITH_WARNING', 'SUCCESSWITHWARNING');

// constants.php end