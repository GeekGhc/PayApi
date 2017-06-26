<?php

/** 通用参数 */
define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']));
define('CLASS_EXT', '.class.php');
define('LIB_PATH', APP_PATH.'/lib/');

/** 支付参数 */

// 微信支付参数
define('APPID', '');
define('MCHID', '');
define('APP_KEY', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');


/** 微信退款证书 */
define('SSLCERT_PATH', APP_PATH.'/key/cert-2/apiclient_cert.pem');
define('SSLKEY_PATH', APP_PATH.'/key/cert-2/apiclient_key.pem');
define('WE_NOTIFY_URL', '');

// 支付宝支付参数
define('APP_ID', '');
define('PID', '');
define('PUBLIC_KEY', APP_PATH.'/key/alipay_public_key.pem');
define('PRIVATE_KEY', APP_PATH.'/key/app_private_key.pem');
define('ALIPAY_PUBLIC_KEY', APP_PATH.'/key/alipay_public_key.pem');
define('ALI_NOTIFY_URL', '');