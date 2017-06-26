<?php

header("Content-type:text/html;charset=utf8");
require  dirname(__FILE__).'/conf/common.php';

class WePayClient
{

    //xml格式返回
    private $sTpl;

    //应用ID
    private $appid;

    //商户号
    private $mch_id;

    //交易类型
    private $trade_type = "APP";

    //商户平台设置的密钥key
    private $key;

    //私钥文件路径
    public $rsaPrivateKeyFilePath;

    //私钥值
    public $rsaPrivateKey;

    //返回数据格式
    public $format = "json";
    //api版本
    public $apiVersion = "1.0";

    // 表单提交字符集编码
    public $postCharset = "UTF-8";

    //使用文件读取文件格式，请只传递该值
    public $alipayPublicKey = null;

    //接口的回调函数
    private $notify_url;

    public function __construct()
    {

    }

    //设置基本数据
    public function setBasicData($appid, $mch_id, $key)
    {
        if (is_string($appid) && is_string($mch_id)) {
            $this->appid = APPID;
            $this->mch_id = MCHID;
            $this->key = APP_KEY;
        }
    }

    //获得随机字符串
    public function getNonceStr()
    {
        $code = "";
        for ($i = 0; $i > 10; $i++) {
            $code .= mt_rand(10000);
        }
        $nonceStrTemp = md5($code);
        $nonce_str = mb_substr($nonceStrTemp, 5, 37);
        return $nonce_str;
    }

    /**
     * 获取签名；
     * @return String 通过计算得到的签名；
     */
    public function getSign($params)
    {
        $this->key = APP_KEY;
        ksort($params);//将参数数组按照参数名ASCII码从小到大排序
        foreach ($params as $key => $item) {
            if (!empty($item)) { //剔除参数值为空的参数
                $newArr[] = $key . '=' . $item; // 整合新的参数数组
            }
        }
        $stringA = implode("&", $newArr); //使用 & 符号连接参数
        $stringSignTemp = $stringA . "&key=" . $this->key; //拼接key
        // key是在商户平台API安全里自己设置的
        $stringSignTemp = MD5($stringSignTemp);//将字符串进行MD5加密
        $sign = strtoupper($stringSignTemp);//将所有字符转换为大写
        return $sign;
    }

    /**
     * 设置通知地址
     * @param  String $url 通知地址；
     */
    public function setNotifyUrl($url)
    {
        if (is_string($url)) {
            $this->notify_url = $url;
        }
    }

    /**
     * 拼装请求的数据
     * @return  String 拼装完成的数据
     */
    private function setSendData($data)
    {
        $this->sTpl = "<xml>
	<appid><![CDATA[%s]]></appid>
	<body><![CDATA[%s]]></body>
	<mch_id><![CDATA[%s]]></mch_id>
	<nonce_str><![CDATA[%s]]></nonce_str>
	<notify_url><![CDATA[%s]]></notify_url>
	<out_trade_no><![CDATA[%s]]></out_trade_no>
	<spbill_create_ip><![CDATA[%s]]></spbill_create_ip>
	<total_fee><![CDATA[%d]]></total_fee>
	<trade_type><![CDATA[%s]]></trade_type>
	<sign><![CDATA[%s]]></sign>
</xml>";
        $nonce_str = $this->getNonceStr();
        $body = $data['body'];
        $out_trade_no = $data['out_trade_no'];
        $total_fee = $data['total_fee'];
        $spbill_create_ip = $data['spbill_create_ip'];
        $trade_type = $this->trade_type;

        $data['appid'] = $this->appid;
        $data['mch_id'] = $this->mch_id;
        $data['nonce_str'] = $nonce_str;
        $data['notify_url'] = $this->notify_url;
        $data['trade_type'] = $this->trade_type;
        $sign = $this->getSign($data);
        $data = sprintf($this->sTpl, $this->appid, $body, $this->mch_id, $nonce_str, $this->notify_url, $out_trade_no, $spbill_create_ip, $total_fee, $trade_type, $sign);
        return $data;
    }


    /**
     * 获取客户端支付信息
     * @param  Array $data 参与签名的信息数组
     * @return String       签名字符串
     */
    public function getClientPay($data)
    {
        $sign = $this->getSign($data);
        return $sign;
    }

    /**
     * 解析xml文档，转化为对象 解析返回数据
     * @param  String $xmlStr xml文档
     * @return Object         返回Obj对象
     */
    public function xmlToObject($xmlStr)
    {
        if (!is_string($xmlStr) || empty($xmlStr)) {
            return false;
        }
        $postObj = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $postObj = json_decode(json_encode($postObj));
        return $postObj;
    }

    /**
     * 接受xml数据 解析并返回
     * @param   $xml_data
     * @return Object         返回Obj对象
     */
    private function setPayData($xml_data)
    {
        $postObj = $this->xmlToObject($xml_data);            //解析返回数据
        if ($postObj->return_code == 'FAIL') {
            echo $postObj->return_msg;            // 如果微信返回错误码为FAIL，则代表请求失败，返回失败信息；
        } else {
            $resignData = array(
                'appid' => $postObj->appid,
                'partnerId' => $postObj->mch_id,
                'prepayId' => $postObj->prepay_id,
                'nonceStr' => $postObj->nonce_str,
                'timeStamp' => time(),
                'package' => 'Sign=WXPay',
            );
            $sign = $this->getClientPay($resignData);
            $resignData['sign'] = $sign;
            return $resignData;
        }
    }

    /**
     * 接收支付结果通知参数
     * @return Object 返回结果对象；
     */
    public function getNotifyData()
    {
        $postXml = file_get_contents('php://input', 'r');      //接受通知参数；
        if (empty($postXml)) {
            return false;
        }
        $postObj = $this->xmlToObject($postXml);
        if ($postObj === false) {
            return false;
        }
        if (!empty($postObj->return_code)) {
            if ($postObj->return_code == 'FAIL') {
                return false;
            }
        }
        return $postObj;
    }

    public function rsaCheckV2($obj)
    {
        if ($obj) {
            $data = array(
                'appid' => $obj->appid,
                'mch_id' => $obj->mch_id,
                'nonce_str' => $obj->nonce_str,
                'result_code' => $obj->result_code,
                'openid' => $obj->openid,
                'trade_type' => $obj->trade_type,
                'bank_type' => $obj->bank_type,
                'total_fee' => $obj->total_fee,
                'cash_fee' => $obj->cash_fee,
                'transaction_id' => $obj->transaction_id,
                'out_trade_no' => $obj->out_trade_no,
                'time_end' => $obj->time_end
            );
            $sign = $this->getSign($data);        // 获取签名 进行验证
            if ($sign == $obj->sign) {
                return "yes";
            } else {
                return $sign;
            }
        }
    }

}