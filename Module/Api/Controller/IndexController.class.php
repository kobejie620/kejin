<?php

namespace Api\Controller;

use Think\Controller;

/**
 * 平台对外接口回调处理器
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/07/26 10:40
 */
class IndexController extends Controller {

    /**
     * 接口应用入口方法
     */
    public function index() {
        $this->createApp($this->_getConfig());
    }

    /**
     * 创建接口应用
     * @param type $config
     * @return \Api\Service\ApiService
     */
    protected function createApp($config) {
        if (empty($config)) {
            die('Not defined interface configuration');
        }
        $apiService = '\\Api\\Service\\WechatService';
        return new $apiService($config);
    }

    /**
     * 获取App的接口配置信息
     * @return type
     */
    protected function _getConfig() {
        $map = array();
        $map['status'] = 2;
        return M('WechatConfig')->where($map)->find();
    }

}
