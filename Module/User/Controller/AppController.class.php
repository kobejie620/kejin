<?php

namespace User\Controller;

class AppController extends UserController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '应用管理';

    /**
     * 配置模块可访问的节点
     * @var type 
     */
    public $access = array(
        'index'  => '应用列表',
        'edit'   => '编辑应用',
        'add'    => '添加应用',
        'del'    => '删除应用',
        'resume' => '启用应用',
        'forbid' => '禁用应用',
    );

    /**
     * 配置模块可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '应用列表',
    );

    protected function _index_data_filter(&$data) {
        foreach ($data as &$row) {
            $row['ids'] = encode($row['id']);
        }
    }

}
