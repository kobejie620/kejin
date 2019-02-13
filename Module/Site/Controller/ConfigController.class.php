<?php

namespace Site\Controller;

class ConfigController extends SiteController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '网站配置';

    /**
     * 设置模块可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '功能开关',
        'edit'  => '开关操作',
    );

    /**
     * 定义可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '功能开关'
    );

    /**
     * 绑定控制器的模型名称
     * @var type
     */
    protected $_bind_model = "SiteConfig";

    /**
     * 网站功能开关
     */
    public function index() {
        $this->edit(null, array(), 'index');
    }

}
