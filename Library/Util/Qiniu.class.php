<?php

namespace Library\Util;

/**
 * 七牛云存储单文件SDK
 * 
 * @version 2.0
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/12/15 14:30:32
 */
class Qiniu {

    public $AccessKey;
    public $SecretKey;
    public $VERSION = '6.1.9';
    public $QINIU_UP_HOST = 'http://upload.qiniu.com';
    public $QINIU_RS_HOST = 'http://rs.qbox.me';
    public $QINIU_RSF_HOST = 'http://rsf.qbox.me';

    /**
     * 七牛SDK构造函数
     * @param type $accessKey
     * @param type $secretKey
     */
    public function __construct($accessKey, $secretKey) {
        $this->AccessKey = $accessKey;
        $this->SecretKey = $secretKey;
    }

    /**
     * 签名 生成Token
     * @param type $data
     * @return type
     */
    public function Sign($data) {
        $sign = hash_hmac('sha1', $data, $this->SecretKey, true);
        return $this->AccessKey . ':' . $this->Encode($sign);
    }

    /**
     * 签名 生成数据Token
     * @param type $data
     * @return type
     */
    public function SignWithData($data) {
        $data = $this->Encode($data);
        return $this->Sign($data) . ':' . $data;
    }

    /**
     * 服务端生成 上传凭证 
     * @param type $bucket
     * @return type
     */
    public function QiniuRSPutPolicy($bucket) {
        $putplicy = new QiniuRSPutPolicy($bucket);
        return $putplicy->Token($this);
    }

    /**
     * 查看单个文件属性信息
     * @param type $bucket
     * @param type $key
     * @return type
     */
    public function QiniuRsStat($bucket, $key) { // => ($statRet, $error)
        $uri = $this->QINIU_RS_HOST . $this->_getRsUri('Stat', array('bucket' => $bucket, 'key' => $key));
        $u = array('path' => $uri);
        $req = new QiniuRequest($u, null, $this->_UserAgent());
        list($resp, $err) = $this->_RoundTrip($req, 'Upload');
        if ($err !== null) {
            return array(null, $err);
        }
        return $this->_ClientRet($resp);
    }

    /**
     * 删除单个文件
     * @param type $bucket
     * @param type $key
     * @return type
     */
    public function QiniuRSDelete($bucket, $key) {
        $option = array('bucket' => $bucket, 'key' => $key);
        $uri = $this->QINIU_RS_HOST . $this->_getRsUri('Delete', $option);
        return $this->_ClientCallNoRet($uri);
    }

    /**
     * 复制单个文件
     * @param type $bucketSrc
     * @param type $keySrc
     * @param type $bucketDest
     * @param type $keyDest
     * @return type
     */
    public function QiniuRSCopy($bucketSrc, $keySrc, $bucketDest, $keyDest) { // => $error
        $option = array('bucketSrc' => $bucketSrc, 'keySrc' => $keySrc, 'bucketDest' => $bucketDest, 'keyDest' => $keyDest);
        $uri = $this->QINIU_RS_HOST . $this->_getRsUri('Copy', $option);
        return $this->_ClientCallNoRet($uri);
    }

    /**
     * 复制单个文件
     * @param type $bucketSrc
     * @param type $keySrc
     * @param type $bucketDest
     * @param type $keyDest
     * @return type
     */
    public function QiniuRSMove($bucketSrc, $keySrc, $bucketDest, $keyDest) { // => $error
        $option = array('bucketSrc' => $bucketSrc, 'keySrc' => $keySrc, 'bucketDest' => $bucketDest, 'keyDest' => $keyDest);
        $uri = $this->QINIU_RS_HOST . $this->_getRsUri('Move', $option);
        return $this->_ClientCallNoRet($uri);
    }

    protected function _RoundTrip($req, $type = 'Upload') {
        switch ($type) {
            case 'Upload':
                $req->Header['Authorization'] = "UpToken {$this->uptoken}";
                return $this->_ClientDo($req);
            case 'Mac':
                $token = $this->SignRequest($req, true);
                $req->Header['Authorization'] = "QBox $token";
                return $this->_ClientDo($req);
            case 'Http':
                return $this->_ClientDo($req);
        }
    }

    protected function _ClientCallNoRet($url, $type = 'Mac') { // => $error
        $u = array('path' => $url);
        $req = new QiniuRequest($u, null, $this->_UserAgent());
        list($resp, $err) = $this->_RoundTrip($req, $type);
        if ($err !== null) {
            return array(null, $err);
        }
        if ($resp->StatusCode === 200) {
            return null;
        }
        return array($resp, $this->_ResponseError($resp));
    }

    protected function _ClientRet($resp) { // => ($data, $error)
        $code = $resp->StatusCode;
        $data = null;
        if ($code >= 200 && $code <= 299) {
            if ($resp->ContentLength !== 0) {
                $data = json_decode($resp->Body, true);
                if ($data === null) {
                    $err_msg = function_exists('json_last_error_msg') ? json_last_error_msg() : "error with content:" . $resp->Body;
                    $err = new QiniuError(0, $err_msg);
                    return array(null, $err);
                }
            }
            if ($code === 200) {
                return array($data, null);
            }
        }
        return array($data, $this->_ResponseError($resp));
    }

    protected function _ResponseError($resp) { // => $error
        $header = $resp->Header;
        $details = $this->_HeaderGet($header, 'X-Log');
        $reqId = $this->_HeaderGet($header, 'X-Reqid');
        $err = new QiniuError($resp->StatusCode, null);
        if ($err->Code > 299) {
            if ($resp->ContentLength !== 0) {
                if ($this->_HeaderGet($header, 'Content-Type') === 'application/json') {
                    $ret = json_decode($resp->Body, true);
                    $err->Err = $ret['error'];
                }
            }
        }
        $err->Reqid = $reqId;
        $err->Details = $details;
        return $err;
    }

    protected function _HeaderGet($header, $key) { // => $val
        $val = $header[$key];
        if (isset($val)) {
            if (is_array($val)) {
                return $val[0];
            }
            return $val;
        } else {
            return '';
        }
    }

    /**
     * 获取操作URI地址
     * @param type $type
     * @param array $option
     * @return type
     */
    protected function _getRsUri($type, Array $option) {
        $uri = array();
        switch ($type) {
            case 'Stat':
            case 'Delete':
                $uri[] = "{$option['bucket']}:{$option['key']}";
                break;
            case 'Copy':
            case 'Move':
                $uri[] = "{$option['bucket']}:{$option['key']}";
                $uri[] = "{$option['bucketDest']}:{$option['keyDest']}";
                break;
        }
        foreach ($uri as &$u) {
            $u = $this->Encode($u);
        }
        return '/' . strtolower($type) . '/' . join($uri, '/');
    }

    /**
     * URL Safe Base64 Encode
     * @param type $str
     * @return type
     */
    public function Encode($str) {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($str));
    }

    /**
     * URL Safe Base64 Dncode
     * @param type $str
     * @return type
     */
    public function Decode($str) {
        $find = array('-', '_');
        $replace = array('+', '/');
        return base64_decode(str_replace($find, $replace, $str));
    }

    /**
     * 读取SDK服务端信息
     * @return type
     */
    protected function _UserAgent() {
        $sdkInfo = "QiniuPHP/{$this->VERSION}";
        $systemInfo = php_uname("s");
        $machineInfo = php_uname("m");
        $envInfo = "({$systemInfo}/{$machineInfo})";
        $phpVer = phpversion();
        return "{$sdkInfo} {$envInfo} PHP/{$phpVer}";
    }

    /**
     * 客户端执行提交操作
     * @param type $req
     * @return array ($resp, $error)
     */
    protected function _ClientDo($req) {
        $ch = curl_init();
        $url = $req->URL;
        $options = array(
            CURLOPT_USERAGENT      => $this->_UserAgent(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => false,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_URL            => $url['path']
        );
        $httpHeader = $req->Header;
        if (!empty($httpHeader)) {
            $header = array();
            foreach ($httpHeader as $key => $parsedUrlValue) {
                $header[] = "$key: $parsedUrlValue";
            }
            $options[CURLOPT_HTTPHEADER] = $header;
        }
        $body = $req->Body;
        if (!empty($body)) {
            $options[CURLOPT_POSTFIELDS] = $body;
        } else {
            $options[CURLOPT_POSTFIELDS] = "";
        }
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $ret = curl_errno($ch);
        if ($ret !== 0) {
            $err = new QiniuError(0, curl_error($ch));
            curl_close($ch);
            return array(null, $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        $responseArray = explode("\r\n\r\n", $result);
        $responseArraySize = sizeof($responseArray);
        $respHeader = $responseArray[$responseArraySize - 2];
        $respBody = $responseArray[$responseArraySize - 1];
        $headers = explode("\r\n", $respHeader);
        $reqid = null;
        $xLog = null;
        foreach ($headers as $header) {
            $header = trim($header);
            if (strpos($header, 'X-Reqid') !== false) {
                list($k, $v) = explode(':', $header);
                $reqid = trim($v);
            } elseif (strpos($header, 'X-Log') !== false) {
                list($k, $v) = explode(':', $header);
                $xLog = trim($v);
            }
        }
        $resp = new QiniuResponse($code, $respBody);
        $resp->Header['Content-Type'] = $contentType;
        $resp->Header["X-Reqid"] = $reqid;
        return array($resp, null);
    }

}

/**
 * Qiniu_Request
 */
class QiniuRequest {

    public $URL;
    public $Header;
    public $Body;
    public $UA;

    public function __construct($url, $body, $UA) {
        $this->URL = $url;
        $this->Header = array();
        $this->Body = $body;
        $this->UA = $UA;
    }

}

/**
 * Qiniu_Response
 */
class QiniuResponse {

    public $StatusCode;
    public $Header;
    public $ContentLength;
    public $Body;

    public function __construct($code, $body) {
        $this->StatusCode = $code;
        $this->Header = array();
        $this->Body = $body;
        $this->ContentLength = strlen($body);
    }

}

/**
 * Qiniu_Error
 */
class QiniuError {

    public $Err;  // string
    public $Reqid;  // string
    public $Details; // []string
    public $Code;  // int

    public function __construct($code, $err) {
        $this->Code = $code;
        $this->Err = $err;
    }

}

/**
 * Qiniu_RS_PutPolicy
 */
class QiniuRSPutPolicy {

    public $Scope;                  //必填
    public $Expires;                //默认为3600s
    public $CallbackUrl;
    public $CallbackBody;
    public $ReturnUrl;
    public $ReturnBody;
    public $AsyncOps;
    public $EndUser;
    public $InsertOnly;             //若非0，则任何情况下无法覆盖上传
    public $DetectMime;             //若非0，则服务端根据内容自动确定MimeType
    public $FsizeLimit;
    public $SaveKey;
    public $PersistentOps;
    public $PersistentPipeline;
    public $PersistentNotifyUrl;
    public $FopTimeout;
    public $MimeLimit;

    public function __construct($scope) {
        $this->Scope = $scope;
    }

    public function Token($mac) { // => $token
        $deadline = $this->Expires;
        if ($deadline == 0) {
            $deadline = 3600;
        }
        $deadline += time();

        $policy = array('scope' => $this->Scope, 'deadline' => $deadline);
        if (!empty($this->CallbackUrl)) {
            $policy['callbackUrl'] = $this->CallbackUrl;
        }
        if (!empty($this->CallbackBody)) {
            $policy['callbackBody'] = $this->CallbackBody;
        }
        if (!empty($this->ReturnUrl)) {
            $policy['returnUrl'] = $this->ReturnUrl;
        }
        if (!empty($this->ReturnBody)) {
            $policy['returnBody'] = $this->ReturnBody;
        }
        if (!empty($this->AsyncOps)) {
            $policy['asyncOps'] = $this->AsyncOps;
        }
        if (!empty($this->EndUser)) {
            $policy['endUser'] = $this->EndUser;
        }
        if (!empty($this->InsertOnly)) {
            $policy['exclusive'] = $this->InsertOnly;
        }
        if (!empty($this->DetectMime)) {
            $policy['detectMime'] = $this->DetectMime;
        }
        if (!empty($this->FsizeLimit)) {
            $policy['fsizeLimit'] = $this->FsizeLimit;
        }
        if (!empty($this->SaveKey)) {
            $policy['saveKey'] = $this->SaveKey;
        }
        if (!empty($this->PersistentOps)) {
            $policy['persistentOps'] = $this->PersistentOps;
        }
        if (!empty($this->PersistentPipeline)) {
            $policy['persistentPipeline'] = $this->PersistentPipeline;
        }
        if (!empty($this->PersistentNotifyUrl)) {
            $policy['persistentNotifyUrl'] = $this->PersistentNotifyUrl;
        }
        if (!empty($this->FopTimeout)) {
            $policy['fopTimeout'] = $this->FopTimeout;
        }
        if (!empty($this->MimeLimit)) {
            $policy['mimeLimit'] = $this->MimeLimit;
        }
        return $mac->SignWithData(json_encode($policy));
    }

}
