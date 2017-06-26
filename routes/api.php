<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');*/

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->group(['namespace' => 'App\Api\Controllers'], function ($api) {
        /*微信*/
        //微信随机字符串
        $api->get('pay/wxpay/getNonceStr','PayController@getNonceStr');
        //微信签名
        $api->post('pay/wxpay/rsaSign', 'PayController@wxpayRsaSign');
        //微信回调地址
        $api->post('pay/wxpayCallback', 'PayController@wxpayCallback');


        /*支付宝*/
        //支付宝签名
        $api->post('pay/alipay/rsaSign','PayController@rsaSign');
        //支付宝回调地址
        $api->post('pay/alipayCallback','PayController@alipayCallback');
        //充值信息
        $api->get('pay/alipay/rechargeAmount','PayController@rechargeAmount');
    });
});
