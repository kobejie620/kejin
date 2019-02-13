<?php

namespace Library\Util\Wxpay;

use Library\Util\Wxpay\SDKRuntimeException;

/**
 * 统一支付接口类
 */
class UnifiedOrder_pub extends Wxpay_client_pub {

    function __construct($out_trade_no, $body, $total_fee, $notify_url, $openid) {
        //设置接口链接
        $this->url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        //设置curl超时时间
        $this->curl_timeout = WxPayConf_pub::CURL_TIMEOUT;
        $this->parameters["trade_type"] = "JSAPI";
        $this->parameters["out_trade_no"] = $out_trade_no;
        $this->parameters["body"] = $body;
        $this->parameters["total_fee"] = $total_fee;
        $this->parameters["notify_url"] = $notify_url;
        $this->parameters["openid"] = $openid;
    }

    /**
     * 生成接口参数xml
     */
    function createXml() {
        try {
            //检测必填参数
            if ($this->parameters["out_trade_no"] == null) {
                throw new SDKRuntimeException("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($this->parameters["body"] == null) {
                throw new SDKRuntimeException("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($this->parameters["total_fee"] == null) {
                throw new SDKRuntimeException("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($this->parameters["notify_url"] == null) {
                throw new SDKRuntimeException("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($this->parameters["trade_type"] == null) {
                throw new SDKRuntimeException("缺少统一支付接口必填参数trade_type！" . "<br>");
            } elseif ($this->parameters["trade_type"] == "JSAPI" &&
                    $this->parameters["openid"] == NULL) {
                throw new SDKRuntimeException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！" . "<br>");
            }
            $this->parameters["appid"] = WxPayConf_pub::APPID; //公众账号ID
            $this->parameters["mch_id"] = WxPayConf_pub::MCHID; //商户号
            $this->parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR']; //终端ip	    
            $this->parameters["nonce_str"] = $this->createNoncestr(); //随机字符串
            $this->parameters["sign"] = $this->getSign($this->parameters); //签名
            return $this->arrayToXml($this->parameters);
        } catch (SDKRuntimeException $e) {
            die($e->errorMessage());
        }
    }

    /**
     * 获取prepay_id
     */
    function getPrepayId() {
        $this->postXml();
        //p($this->response);
        $this->result = $this->xmlToArray($this->response);
        $prepay_id = $this->result["prepay_id"];
        return $prepay_id;
    }

    /**
     * 	作用：post请求xml
     */
    function postXml() {
        $xml = $this->createXml();
        $this->response = $this->postXmlCurl($xml, $this->url, $this->curl_timeout);
        return $this->response;
    }

    /**
     * 	作用：设置jsapi的参数
     */
    public function getParameters() {
        $jsApiObj["appId"] = WxPayConf_pub::APPID;
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $prePayId = $this->getPrepayId();
        $jsApiObj["package"] = "prepay_id=$prePayId";
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $this->parameters = $jsApiObj;

        return $this->parameters;
    }

}
