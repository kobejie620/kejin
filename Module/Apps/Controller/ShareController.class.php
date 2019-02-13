<?php

namespace Apps\Controller;

/**
 * 分享有礼控制器
 * @author jindewen <jindewen@21cn.com>
 * @date 2015/03/27 15:51:47
 */
class ShareController extends AppsController {

    /**
     * 定义模型名称
     * @var type 
     */
    public $ptitle = "分享有礼";

    /**
     * 设定可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '分享列表',
        'add' => '添加文章',
        'edit' => '修改文章',
        'del' => '删除文章',
        'resume' => '启用',
        'forbid' => '禁用',
    );

    /**
     * 设定可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '分享列表'
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'AppsShare';

    protected function _filter(&$model, &$map) {
        /* 查询条件 */
        ($kw = I("get._kw")) && $map['title'] = array("like", "%$kw%");
    }

}
