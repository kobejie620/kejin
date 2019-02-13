<?php

namespace Admin\Model;

/**
 * 系统菜单模型类
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 12:12:12
 */
class SysMenuModel extends AdminModel {

    private $_open_uri = null;
    private $_isset_open = false;

    public function _initialize() {
        parent::_initialize();
        $this->_open_uri = val('_URI_') ? val('_URI_') : url_filter();
    }

    /**
     * 获取用户的系统菜单数据
     * 
     * @access public
     * @author zoujingli <zoujingli@qq.com>
     * @update 2014/07/01 21:58
     */
    public function getRoleMenu($map = array('status' => 2)) {
        $menu_tree = $this->_mergeNav(0, $this->_parseMenuTree($this->getMenuList($map), true));
        $this->_removeEmptyMenuAndSetOpenMenu($menu_tree, $this->_open_uri);
        $this->_isset_open || $this->_removeEmptyMenuAndSetOpenMenu($menu_tree, MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME);
        return $menu_tree;
    }

    /**
     * 获取全部系统菜单树
     * 
     * @return type
     */
    public function getMenuTree() {
        return $this->_parseMenuTree(sort_array_by_key($this->getMenuList(), 'sort', 1));
    }


    /**
     * 查询菜单列表
     * @param array $map
     * @return mixed
     */
    public function getMenuList($map = array()) {
        $list = $this->where($map)->select();
        return $list;
    }

    /**
     * 移除空菜单并设置激活的菜单
     * 
     * @param type $data
     * @return type
     */
    protected function _removeEmptyMenuAndSetOpenMenu(&$data, $uri = '', &$pMenu = null, &$ppMenu = null) {
        foreach ($data as $key => &$menu) {
            if (empty($menu['sub']) && !trim($menu['url'], '#')) {
                // 如果子菜单为空，而且自身链接为#，需移除
                unset($data[$key]);
                if (!is_null($pMenu) && empty($pMenu['sub']) && !trim($pMenu['url'], '#')) {
                    $pMenu = null;
                }
                if (!is_null($ppMenu) && empty($ppMenu['sub']) && !trim($ppMenu['url'], '#')) {
                    $ppMenu = null;
                }
                continue;
            } elseif (!empty($menu['sub'])) {
                // 如果存在子菜单
                $menu['_class'] = 'parent';
                $this->_removeEmptyMenuAndSetOpenMenu($menu['sub'], $uri, $menu, $pMenu);
            } else if (isset($menu['url']) && stripos($menu['url'], $uri) !== false) {
                // 符合当前合适的菜单并选中
                $menu['_class'] = 'active';
                is_null($pMenu) || $pMenu['_class'] = 'parent active';
                is_null($ppMenu) || $ppMenu['_class'] = 'parent active';
                is_null($ppMenu) || $menu['_class'].=' sub';
                $this->_isset_open = true;
            }
        }
    }

    /**
     * 递归处理树形数据
     * 
     * @param int $pid 数据起始ID
     * @param  array $data 原始的数据列表
     * @return array
     * 
     * @access private
     * @author zoujingli <zoujingli@qq.com>
     * @update 2014/07/01 21:58
     */
    protected function _mergeNav($pid, $data) {
        $newNav = array();
        foreach ($data as $vo) {
            if ($vo['pid'] == $pid) {
                $vo['sub'] = $this->_mergeNav($vo['id'], $data);
                $newNav[$vo['id']] = $vo;
            }
        }
        return \sort_array_by_key($newNav, 'sort', 1);
    }

    /**
     * 菜单显示时的数据过滤处理
     * 
     * @param type $data 需要处理的数据
     * @param type $auth 是否应用权限检查
     * @return type
     */
    protected function _parseMenuTree($data, $auth = false) {
        foreach ($data as $key => &$vo) {
            $vo['pId'] = $vo['pid'];
            $vo['name'] = $vo['title'];
            if (empty($vo['url']) or $vo['url'] === '#') {
                unset($vo['url']);
            } else {
                $vo['url'] = U($vo['url'], $vo['request']);
            }
            /* 如果菜单没有授权，直接移除掉 */
            if ($auth && !auth($vo['code'])) {
                unset($data[$key]);
            }
        }
        return $data;
    }

}
