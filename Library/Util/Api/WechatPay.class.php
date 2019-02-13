<?php

namespace Library\Util\Api;

/**
 * 微信支付SDK
 * @author zoujingli <zoujingli@qq.com>
 * @date 2015/05/13 12:12:00
 */
class WechatPay {

    /** 支付接口基础地址 */
    const MCH_BASE_URL = 'https://api.mch.weixin.qq.com';

    /** 公众号appid */
    protected $appid;

    /** 商户身份ID */
    protected $mch_id;

    /** 商户支付密钥Key */
    protected $partnerKey;

    /** 商户路径 */
    protected $ssl_cer;
    protected $ssl_key;

    /** 执行错误消息及代码 */
    public $errMsg;
    public $errCode;

    /**
     * 支付SDK构造函数
     * @param type $options
     */
    public function __construct($options) {
        $this->appid = isset($options['appid']) ? $options['appid'] : '';
        $this->mch_id = isset($options['mch_id']) ? $options['mch_id'] : $options['partnerid'];
        $this->partnerKey = isset($options['partnerkey']) ? $options['partnerkey'] : '';
        $this->ssl_cer = isset($options['ssl_cer']) ? $options['ssl_cer'] : '';
        $this->ssl_key = isset($options['ssl_key']) ? $options['ssl_key'] : '';
    }

    /**
     * 设置标配的请求参数，生成签名，生成接口参数xml
     * @param type $data
     * @return type
     */
    protected function createXml($data) {
        isset($data['appid']) || $data['appid'] = $this->appid;
        isset($data['mch_id']) || $data['mch_id'] = $this->mch_id;
        isset($data['nonce_str']) || $data['nonce_str'] = WechatPayCommon::createNoncestr();
        $data["sign"] = WechatPayCommon::getPaySign($data, $this->partnerKey);
        return WechatPayCommon::array2xml($data);
    }

    /**
     * POST提交XML
     * @param type $data
     * @param type $url
     * @return type
     */
    public function postXml($data, $url) {
        return WechatPayCommon::post($this->createXml($data), $url);
    }

    /**
     * 使用证书post请求XML
     * @param type $data
     * @param type $url
     * @return type
     */
    function postXmlSSL($data, $url) {
        return WechatPayCommon::ssl_post($this->createXml($data), $url, $this->ssl_cer, $this->ssl_key);
    }

    /**
     * POST提交获取Array结果
     * @param type $data 需要提交的数据
     * @param type $url
     * @param type $method
     * @return type
     */
    public function getArrayResult($data, $url, $method = 'postXml') {
        return WechatPayCommon::xml2array($this->$method($data, $url));
    }

    /**
     * 解析返回的结果
     * @param type $result
     * @return boolean
     */
    protected function _parseResult($result) {
        if (empty($result)) {
            $this->errCode = 'result error';
            $this->errMsg = '解析返回结果失败';
            return false;
        }
        if ($result['return_code'] !== 'SUCCESS') {
            $this->errCode = $result['return_code'];
            $this->errMsg = $result['return_msg'];
            return false;
        }
        if (isset($result['err_code'])) {
            $this->errMsg = $result['err_code_des'];
            $this->errCode = $result['err_code'];
            return false;
        }
        return $result;
    }

    /**
     * 获取预支付ID
     * @param type $openid          用户openid，JSAPI必填
     * @param type $body            商品标题
     * @param type $out_trade_no    第三方订单号
     * @param type $total_fee       订单总价
     * @param type $notify_url      支付成功回调地址
     * @param type $trade_type      支付类型JSAPI|NATIVE|APP
     * @return boolean
     */
    public function getPrepayId($openid, $body, $out_trade_no, $total_fee, $notify_url, $trade_type = "JSAPI") {
        $postdata = array(
            "openid" => $openid,
            "body" => $body,
            "out_trade_no" => $out_trade_no,
            "total_fee" => $total_fee,
            "notify_url" => $notify_url,
            "trade_type" => $trade_type,
            "spbill_create_ip" => get_client_ip(),
        );
        $result = $this->getArrayResult($postdata, self::MCH_BASE_URL . '/pay/unifiedorder');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return $result['prepay_id'];
    }

    /**
     * 创建JSAPI支付参数包
     * @param type $prepay_id
     * @return type
     */
    public function createMchPay($prepay_id) {
        $option = array();
        $option["appId"] = $this->appid;
        $option["timeStamp"] = (string) time();
        $option["nonceStr"] = WechatPayCommon::createNoncestr();
        $option["package"] = "prepay_id={$prepay_id}";
        $option["signType"] = "MD5";
        $option["paySign"] = WechatPayCommon::getPaySign($option, $this->partnerKey);
        return $option;
    }

    /**
     * 关闭订单
     * @param type $out_trade_no
     * @return boolean
     */
    public function closeOrder($out_trade_no) {
        $data = array('out_trade_no' => $out_trade_no);
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/pay/closeorder');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return ($result['return_code'] === 'SUCCESS');
    }

    /**
     * 查询订单详情
     * @param type $out_trade_no
     */
    public function queryOrder($out_trade_no) {
        $data = array('out_trade_no' => $out_trade_no);
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/pay/orderquery');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return $result;
    }

    /**
     * 订单退款接口
     * @param type $out_trade_no 商户订单号
     * @param type $transaction_id 微信订单号
     * @param type $out_refund_no 商户退款订单号
     * @param type $total_fee 商户订单总金额
     * @param type $refund_fee 退款金额
     * @param type $op_user_id 操作员ID，默认商户ID
     * @return boolean
     */
    public function refund($out_trade_no, $transaction_id, $out_refund_no, $total_fee, $refund_fee, $op_user_id = null) {
        $data = array();
        $data['out_trade_no'] = $out_trade_no;
        $data['transaction_id'] = $transaction_id;
        $data['out_refund_no'] = $out_refund_no;
        $data['total_fee'] = $total_fee;
        $data['refund_fee'] = $refund_fee;
        $data['op_user_id'] = empty($op_user_id) ? $this->mch_id : $op_user_id;
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/secapi/pay/refund', 'postXmlSSL');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return ($result['return_code'] === 'SUCCESS');
    }

    /**
     * 退款查询接口
     * @param type $out_trade_no
     * @return boolean
     */
    public function refundQuery($out_trade_no) {
        $data = array();
        $data['out_trade_no'] = $out_trade_no;
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/pay/refundquery');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return $result;
    }

    /**
     * 获取对账单
     * @param type $bill_date 账单日期，如 20141110
     * @param type $bill_type ALL|SUCCESS|REFUND|REVOKED
     * @return boolean
     */
    public function getBill($bill_date, $bill_type = 'ALL') {
        $data = array();
        $data['bill_date'] = $bill_date;
        $data['bill_type'] = $bill_type;
        $result = $this->postXml($data, self::MCH_BASE_URL . '/pay/downloadbill');
        $json = WechatPayCommon::xml2array($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $result;
    }

    /**
     * 二维码链接转成短链接
     * @param type $url
     * @return boolean
     */
    public function shortUrl($url) {
        $data = array();
        $data['long_url'] = $url;
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/tools/shorturl');
        if (!$result || $result['return_code'] !== 'SUCCESS') {
            $this->errCode = $result['return_code'];
            $this->errMsg = $result['return_msg'];
            return false;
        }
        if (isset($result['err_code'])) {
            $this->errMsg = $result['err_code_des'];
            $this->errCode = $result['err_code'];
            return false;
        }
        return $result['short_url'];
    }

    /**
     *  微信企业红包发放接口
     * @param type $openid 用户openid
     * @param type $mch_billno 订单号 $mch_id + 随机数
     * @param type $send_name 发送账号昵称
     * @param type $total_amount 发送金额
     * @param type $wishing 祝福语
     * @param type $act_name 活动名称
     * @param type $remark 备注
     * @return boolean
     */
    public function sendRedPack($openid, $mch_billno, $send_name, $total_amount, $wishing, $act_name, $remark, $total_num = '1') {
        $postdata = array(
            "nonce_str" => $this->generateNonceStr(),
            "mch_billno" => $mch_billno,
            "mch_id" => $this->mch_id,
            "wxappid" => $this->appid,
            "nick_name" => $send_name,
            "send_name" => $send_name,
            "re_openid" => $openid,
            "total_amount" => $total_amount,
            "min_value" => $total_amount,
            "max_value" => $total_amount,
            "total_num" => $total_num,
            "wishing" => $wishing,
            "client_ip" => get_client_ip(),
            "act_name" => $act_name,
            "remark" => $remark
        );
        $postdata["sign"] = $this->getPaySign($postdata);
        $result = WechatPayCommon::ssl_post(WechatPayCommon::arrayToXmlHb($postdata), self::MCH_BASE_URL . '/mmpaymkttransfers/sendredpack', $this->ssl_cer, $this->ssl_key);
        if ($result) {
            $json = (array) simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'] . json_encode($postdata);
                return false;
            }
            return $json;
        }
        return false;
    }

    public function getHbInfo($mch_billno) {
        $postdata = array(
            "nonce_str" => $this->generateNonceStr(),
            "mch_billno" => $mch_billno,
            "mch_id" => $this->mch_id,
            "appid" => $this->appid,
            "bill_type" => 'MCHT',
        );
        $postdata["sign"] = $this->getPaySign($postdata);
        $result = WechatPayCommon::ssl_post(WechatPayCommon::arrayToXmlHb($postdata), self::MCH_BASE_URL . '/mmpaymkttransfers/gethbinfo', $this->ssl_cer, $this->ssl_key);
        if ($result) {
            $json = (array) simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'] . json_encode($postdata);
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     *  支付
     * 	作用：生成签名
     */
    public function getPaySign($Obj) {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->partnerKey;
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     * 生成随机字串
     * @param number $length 长度，默认为16，最长为32字节
     * @return string
     */
    private function generateNonceStr($length = 16) {
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     *  微信支付3.37
     * 	作用：格式化参数，签名过程需要使用
     * 
     */
    function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

}
