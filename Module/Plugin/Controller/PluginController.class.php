<?php

namespace Plugin\Controller;

use Think\Controller;

class PluginController extends Controller {

    /**
     * 获取客户端域名
     * @return type
     */
    protected function _getClientDomain() {
        $parse_url = I('server.HTTP_REFERER', '', 'parse_url');
        return $parse_url['host'];
    }

    /**
     * 输出header头信息
     */
    protected function _header($type = 'javascript') {
        switch (strtolower($type)) {
            case 'javascript':
                header('Content-Type: application/x-javascript; charset=UTF-8');
                break;
            case 'json':
                header('Content-type: application/json; charset=UTF-8');
                break;
        }
    }

    /**
     * 获取系统七牛配置
     * 
     * @return boolean
     */
    protected function __config() {
        $key = 'token_' . md5($this->_getClientDomain());
        $appid = I('get.token', session($key), 'trim,decode');
        $map = array();
        $map['status'] = 2;
        $map['id|host'] = array($appid, $this->_getClientDomain(), '_multi' => true);
        $app = M('App')->where($map)->find();

        if (empty($app)) {
            $ak = get_sysconfig('qiniu_accesskey');
            $sk = get_sysconfig('qiniu_secretkey');
            $bk = get_sysconfig('qiniu_bucket');
            $dm = get_sysconfig('qiniu_domain');
            session($key, null);
        } else {
            $ak = $app['qiniu_accesskey'];
            $sk = $app['qiniu_secretkey'];
            $bk = $app['qiniu_bucket'];
            $dm = $app['qiniu_domain'];
            $data = array();
            $data['id'] = $appid;
            $data['get_number'] = intval($app['get_number']) + 1;
            $data['get_last_date'] = get_now_date();
            $data['get_last_ip'] = get_client_ip();
            $data['get_last_domain'] = $this->_getClientDomain();
            M('App')->save($data);
            session($key, encode($appid));
        }

        $this->assign('config', $app);
        $this->assign('token', encode($appid));

        empty($dm) && $dm = "{$bk}.qiniudn.com";
        define('AKEY', $ak);
        define('SKEY', $sk);
        define('BUCKET', $bk);
        define('DOMAIN', "http://{$dm}/");

        return array('ak' => $ak, 'sk' => $sk, 'bk' => $bk, 'dm' => $dm);
    }

}
