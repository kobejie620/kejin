<?php

namespace Admin\Widget;

use Think\Controller;

/**
 * 系统菜单挂件
 * 
 * @author zoujingli <zoujingli@qq.com>
 */
class MenuWidget extends Controller {

    /**
     * 程序初始化方法
     * 读取系统菜单数据并同时表态缓存数据
     * @staticvar array $data
     */
    public function _initialize() {
        static $data = array();
        if (empty($data)) {
            /* 读取菜单数据 */
            $menuModel = new \Admin\Model\SysMenuModel();
            $menuList = $menuModel->getRoleMenu();
            foreach ($menuList as &$vo) :
                if (!empty($vo['sub'])) :
                    $item = $vo['sub'][0];
                    $vo['url'] = empty($item['sub']) ? $item['url'] : $item['sub'][0]['url'];
                endif;
            endforeach;
            $data = $menuList;
        }
        $this->assign('sys_menu', $data);
    }

    /**
     * 显示顶部主菜单
     */
    public function topMenu() {
        $this->display(T('Admin@Public/index_navtop'));
    }

    /**
     * 显示左侧菜单
     */
    public function leftMenu() {
        $this->display(T('Admin@Public/index_navleft'));
    }

}
