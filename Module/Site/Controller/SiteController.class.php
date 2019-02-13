<?php

namespace Site\Controller;

use Wechat\Controller\WechatController;

class SiteController extends WechatController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $gtitle = '微官网';

    /**
     * 微官网配置数据
     * @var type 
     */
    protected $wapsite = null;

    public function _initialize() {
        parent::_initialize();
        $this->wapsite = M('SiteConfig')->find();
        $this->assign('wapsite', $this->wapsite);
    }

}
