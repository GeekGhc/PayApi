<?php

namespace App\Api\Controllers;

include_once __DIR__ . '/pay/WePayClient.php';

class PayController extends BaseController
{
    protected $wxPay;
    protected $aliPay;

    public function __construct()
    {
        $this->wxPay = new \WePayClient();
    }

    //微信获取随机字符串
    public function getNonceStr()
    {
        $data = [
            'code'=>1,
            'data'=> $this->wxPay->getNonceStr()
        ];
        return response()->json($data);
    }

    //微信签名
    public function wxpayRsaSign(Request $request)
    {
        $param = $request->getContent();
        $signStr = $this->wxPay->getSign(urldecode($param));
        return $signStr;
    }

    //微信支付回调接口
    public function wxpayCallback(Request $request)
    {
        $obj = $this->wxPay->getNotifyData();
        if ($obj->return_code != "SUCCESS") {
            Storage::disk('local')->put('file.txt', "failed");
            return response('failure');
        } else {
            $request = $this->wxPay->rsaCheckV2($obj);
            if ($request) {
                Storage::disk('local')->put('file.txt', "success");
                $row = DB::table('ys_recharge')->where('out_trade_no', $obj->out_trade_no)->update(['recharge_time' => strtotime($obj->time_end), 'status' => 1, 'subject' => "充值言士币" . '：' . $obj->cash_fee, 'trade_no' => $obj->transaction_id]);
                if ($row) {
                    $uid = DB::table('ys_recharge')->where('out_trade_no', $obj->out_trade_no)->value('uid');
                    $ys_coin = DB::table('ys_user')->where('id', $uid)->value('ys_coin');
                    $coin_count = DB::table('ys_recharge')->where('out_trade_no', $obj->out_trade_no)->value('coin_count');
                    $rows = DB::table('ys_user')->where('id', $uid)->update(['ys_coin' => $ys_coin + $coin_count]);
                } else {
                    return response('failure');
                }
                if ($rows) {
                    $reply = "<xml>
                    <return_code><![CDATA[SUCCESS]]></return_code>
                    <return_msg><![CDATA[OK]]></return_msg>
                </xml>";
                    echo $reply;      // 向微信后台返回结果
                } else {
                    return response('failure');
                }
            } else {
                Storage::disk('local')->put('file.txt', json_encode($obj));
                Storage::disk('local')->put('file.txt', "failed-two");
                return response('failure');
            }
        }
    }
}