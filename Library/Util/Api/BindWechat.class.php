<?php

namespace Library\Util\Api;

/**
 * 自动绑定微信公众平台账号
 * 
 * @param type $username 公众平台登录账号
 * @param type $password 公众平台登录密码
 * @param type $api 高级接口回调地址
 * @param type $token 高级接口Token
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/03 13:52:00
 * 
 * <code>
 * $autoBind = new BindWechat($config);
 * //自动绑定
 * $result = $autoBind->autoBind();
 * if ($result === false) {
 * 		echo $autoBind->getError();
 * } else {
 * 		echo 'bind success';
 * }
 * //获取配置信息
 * $autoBind->getInfo();
 * </code>
 */
class BindWechat {

    /**
     * 消息内容
     * @var type 
     */
    protected $error;

    /**
     * 基础操作链接
     * @var type 
     */
    protected $base_url;

    /**
     * 会话Cookie文件
     * @var type 
     */
    protected $cookie_file;

    /**
     * 登录验证Token
     * @var type 
     */
    protected $web_token;

    /**
     * 公众平台登录账号
     * @var type 
     */
    protected $username;

    /**
     * 公众平台登录密码
     * @var type 
     */
    protected $password;

    /**
     * 高级接口回调地址
     * @var type 
     */
    protected $redirectUri;

    /**
     * 高级接口Token
     * @var type 
     */
    protected $token;

    /**
     * 消息加密密钥
     * @var type 
     */
    protected $encoding_aeskey;
    protected $encoding_mode = 0;

    /**
     * 自动绑定微信公众平台账号
     * 
     * @param type $username 公众平台登录账号
     * @param type $password 公众平台登录密码
     * @param type $api 高级接口回调地址
     * @param type $token 高级接口Token
     */
    public function __construct($config) {
        $this->base_url = 'https://mp.weixin.qq.com';
        $this->cookie_file = tempnam('./temp', 'cookie');
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->redirectUri = $config['redirectUri'];
        $this->token = $config['token'];
        $this->encoding_aeskey = $config['encodingaeskey'];
        $this->encoding_mode = $config['encryptmode'];
    }

    /**
     * 获取执行错误信息
     * 
     * @return type
     */
    public function getError() {
        return $this->error;
    }

    /**
     * 自动绑定
     * 
     * @return boolean 对接失败返回False 可能通过getMsg方法获取错误信息
     */
    public function autoBind() {
        if (false === $this->login()) {
            $this->error = '模拟登录失败';
            return false;
        }
        if (false === $this->_bind()) {
            $this->error = '参数设置失败';
            return false;
        }
        return true;
    }

    /**
     * 绑定指定账号的Api及Token
     * 
     * @return type
     */
    protected function _bind() {
        //设置高级接口回调地址
        $url = '/advanced/callbackprofile?t=ajax-response&lang=zh_CN&token=' . $this->web_token;
        $data = array();
        $data['url'] = $this->redirectUri;
        $data['callback_token'] = $this->token;
        $data['encoding_aeskey'] = $this->encoding_aeskey;
        $data['callback_encrypt_mode'] = $this->encoding_mode;
        $data['operation_seq'] = date('YmdH');
        if (false === $this->parseResult($this->post($url, $data))) {
            return false;
        }
        //启用高级接口模式
        $data = array();
        $data['flag'] = 1;
        $data['type'] = 2;
        $data['f'] = 'json';
        $data['token'] = $this->web_token;
        $url = '/misc/skeyform?form=advancedswitchform&lang=zh_CN';
        if (false === $this->parseResult($this->post($url, $data))) {
            return false;
        }
        //设置OAuth2.0网页授权
        $data = array();
        $data['ajax'] = 1;
        $data['lang'] = 'zh_CN';
        $data['f'] = 'json';
        $data['domain'] = I('server.HTTP_HOST');
        $data['token'] = $this->web_token;
        $url = '/merchant/myservice?action=set_oauth_domain&f=json';
        if (false === $this->parseResult($this->post($url, $data))) {
//            return false;
        }
        return true;
    }

    /**
     * 正则解析HTML
     * 
     * @param type $html
     * @param type $pattern
     * @return string|array
     */
    protected function _parseHtml($html, $pattern) {
        $tmpArr = array();
        $result = preg_match($pattern, $html, $tmpArr);
        if ($result) {
            return $tmpArr[1];
        }
        return '';
    }

    /**
     * 获取用户的基本信息
     * 
     * @return array
     */
    public function getApiInfo() {
        /* 获取用户微信基本信息 */
        $url = '/cgi-bin/settingpage?t=setting/index&action=index&token=' . $this->web_token . '&lang=zh_CN';
        $setting = $this->get($url);

        /* 正则调试区 */
        $title = $this->_parseHtml($setting, '|名称.*?<div class="meta_content">\s*(.*?)\s*</div>|is');
        //$pattern = '|登录邮箱.*?<div class="meta_content">(.*?)</div>.*?</li>|is';
        $openid = $this->_parseHtml($setting, '|原始ID.*?<div class="meta_content">.*?<span>(.*?)</span>.*?</div>|is');
        $img_url = $this->_parseHtml($setting, '|头像.*?<div class="meta_content">\s*<img class="avatar" src="(.*?)" />.*?</div>|is');
        $img = $this->_saveFile($img_url, $openid);
        $wechatid = $this->_parseHtml($setting, '|微信号.*?<div class="meta_content">\s*<span>(.*?)\s*<|is');
        $apptype = $this->_parseHtml($setting, '|类型.*?<div class="meta_content">\s*(.*?)\s*</div>|is');
        $appverify = $this->_parseHtml($setting, '|认证情况.*?<div class="meta_content">\s*(.*?)\s*<|is');
        $qrc_url = $this->_parseHtml($setting, '|二维码.*?<img src="(.*?)".*?/>|is');
        $qrc_img = $this->_saveFile($qrc_url, $openid . '_qrc');
        unset($setting);

        /* 获取用户开发者信息 */
        $apiurl = '/advanced/advanced?action=dev&t=advanced/dev&token=' . $this->web_token . '&lang=zh_CN';
        $apihtml = $this->get($apiurl);

        /* 正则调试区 */
        $appid = $this->_parseHtml($apihtml, '|AppId.*?<div class="frm_controls frm_vertical_pt">\s*(.*?)\s*</div>|is');
        unset($apihtml);

        return array(
            /* 基本信息 */
            'img'       => trim($img),
            'qrc_img'   => trim($qrc_img),
            'title'     => trim($title),
            'token'     => trim($this->token),
            'apptype'   => mb_substr(trim($apptype), 0, 3, 'UTF-8'),
            'appverify' => trim($appverify),
            'openid'    => trim($openid),
            'wechatid'  => trim($wechatid),
            /* 高级接口 */
            'appid'     => trim($appid),
        );
    }

    /**
     * 保存远程服务器上的文件
     * 
     * @param string $remote 远程文件名
     * @param string $localName 本地保存文件名
     * @return type
     */
    private function _saveFile($remote, $localName) {
        //头像存放目录及其文件
        $name = "Static/Uploads/weixin/{$localName}.jpg";
        $filename = APP_ROOT . $name;
        $dirname = dirname($filename);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
        $this->download($remote, $filename);
        return __ROOT__ . '/' . $name;
    }

    /**
     * 登录微信公众平台
     * 
     * @return boolean
     */
    protected function login() {
        $url = '/cgi-bin/login?lang=zh_CN';
        $data = "username={$this->username}&pwd=" . md5($this->password) . "&f=json";
        $result = $this->parseResult($this->post($url, $data));
        if ($result === false) {
            return false;
        }
        $param = array();
        parse_str(parse_url($result['redirect_url'], PHP_URL_QUERY), $param);
        $this->web_token = $param['token'];
    }

    /**
     * 解析返回的结果
     * 
     * @param type $result
     * @param type $type
     * @return boolean
     */
    protected function parseResult($result, $type = 'json_decode') {
        is_string($result) && $result = $type($result, true);
        if (empty($result['base_resp']['ret']) && empty($result['ret'])) {
            return $result;
        } else {
            $this->error = empty($result['base_resp']['err_msg']) ? $result['msg'] : $result['base_resp']['err_msg'];
            return false;
        }
    }

    /**
     * POST模拟提交数据
     * 
     * @param type $url
     * @param type $data
     * @return type
     */
    public function post($url, $data) {
        $ch = curl_init($this->base_url . $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $this->base_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $tmpInfo;
    }

    /**
     * GET 方式获取页面信息
     * 
     * @param type $url
     * @return type'
     */
    public function get($url) {
        $ch = curl_init($this->base_url . $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $this->base_url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $tmpInfo;
    }

    /**
     * 采集远程文件
     * @access public
     * @param string $remote 远程文件名
     * @param string $local 本地保存文件名
     * @return mixed
     */
    public function download($remote, $local) {
        $cp = curl_init($this->base_url . $remote);
        $fp = fopen($local, "w");
        curl_setopt($cp, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5');
        curl_setopt($cp, CURLOPT_REFERER, $this->base_url);
        curl_setopt($cp, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($cp, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        curl_exec($cp);
        curl_close($cp);
        fclose($fp);
    }

}
