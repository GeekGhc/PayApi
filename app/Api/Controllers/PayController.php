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


    //支付宝签名
    public function rsaSign(Request $request)
    {
        $privateKey = Config::get('constants.PRIVATE_KEY');
        $param = $request->getContent();
        $sign = $this->alonersaSign(urldecode($param), $privateKey);
        $newStr = $param . '&sign=' . urlencode($sign);
        return $newStr;
    }
    /**
     * RSA单独签名方法，未做字符串处理,字符串处理见getSignContent()
     * @param $data 待签名字符串
     * @param $privatekey 商户私钥，根据keyfromfile来判断是读取字符串还是读取文件，false:填写私钥字符串去回车和空格 true:填写私钥文件路径
     * @param $signType 签名方式，RSA:SHA1     RSA2:SHA256
     * @param $keyfromfile 私钥获取方式，读取字符串还是读文件
     * @return string
     * @author mengyu.wh
     */
    public function alonersaSign($data, $privatekey, $signType = "RSA2", $keyfromfile = false)
    {

        if (!$keyfromfile) {
            $priKey = $privatekey;
            $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($priKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        } else {
            $priKey = file_get_contents($privatekey);
            $res = openssl_get_privatekey($priKey);
        }

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }

        if ($keyfromfile) {
            openssl_free_key($res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
    //充值信息  根据实际项目订单内容
    public function rechargeAmount()
    {

    }
    //支付宝支付回调接口
    public function alipayCallback(Request $request)
    {
        $array = array();
        $dataArray = array();
        $data = $request->getContent();
        if (!$data) {
            return response('failure');
        }
        $data = urldecode($data);
        $array = explode('&', $data);
        foreach ($array as $key => $val) {
            $k = explode('=', $val);
            $dataArray[$k[0]] = array_key_exists(1, $k) ? $k[1] : '';
        }
        if ($dataArray['trade_status'] != 'TRADE_SUCCESS') {
            return response('failure');
        } else {
            unset($dataArray['sign_type']);
            //项目实际逻辑代码
        }
    }
}