<?php

namespace Admin\Controller;

use Library\Util\Node;

/**
 * 菜单配置
 * 
 * @author anyon <zoujingli@qq.com>
 */
class MenuController extends AdminController {

    /**
     * 模块标题
     * @var type 
     */
    public $ptitle = '系统菜单';

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'SysMenu';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index'  => '菜单列表',
        'add'    => '添加菜单',
        'edit'   => '编辑菜单',
        'del'    => '删除菜单',
        'resume' => '启用菜单',
        'forbid' => '禁用菜单',
    );

    /**
     * 定义可设置菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '菜单列表',
    );

    /**
     * 关闭分页
     * @var type 
     */
    protected $_page_on = false;

    /**
     * 定义菜单链接打开方式
     * @var type 
     */
    protected $targetList = array(
        '_self'   => '本窗口打开',
        '_blank'  => '新窗口打开',
        '_parent' => '父窗口打开',
        '_top'    => '顶级窗口打开',
    );

    /**
     * 列表数据处理
     * 
     * @param type $data
     */
    protected function _index_data_filter(&$data) {
        $data = get_select_tree($data);
        foreach ($data as &$row) {
            $row['icon'] = empty($row['ico']) ? '' : '<i class="' . $row['ico'] . '"></i> ';
            $row['title'] = $row['spl'] . $row['icon'] . $row['title'];
            $row['ids'] = get_array_tree_lower_ids($data, $row['id']);
            $row['target'] = $this->targetList[$row['target']];
        }
    }

    /**
     * 添加子菜单
     */
    public function _before_add() {
        $_REQUEST['pid'] = $_GET['pid'] = I('get.id', 0, 'trim,intval');
        unset($_GET['id'], $_REQUEST['id']);
    }

    /**
     * 表单操作过滤函数
     */
    protected function _edit_filter(&$model, &$data) {
        if (!IS_POST) {
            if (I('get.action') === 'getNodeTree') {
                $this->ajaxReturn($this->_getMenuNodeData());
            } else {
                $map = array();
                $map['status'] = 2;
                $list = (array) M($this->_bind_model)->where($map)->select();
                $list[] = array('title' => '主菜单', 'id' => 0, 'pid' => -1);
                $list = get_select_tree($list);
                //去除自己的子项，防止递归循环
                if (!empty($data['id'])) {
                    $sub_ids = get_array_tree_lower_ids($list, $data['id']);
                    foreach ($list as $key => $value) {
                        if (in_array(intval($value['id']), $sub_ids)) {
                            unset($list[$key]);
                        }
                    }
                }
                $this->assign('list', $list);
                $this->assign('target', $this->targetList);
            }
        }
    }

    protected function _add_filter(&$data) {
        $this->_edit_filter($data);
    }

    /**
     * 读取菜单节点数据
     * @return type
     */
    private function _getMenuNodeData() {
        $list = Node::getNodeArrayTree(get_dir(APP_PATH));
        unset($list['Admin']['Public'], $list['Admin']['Index']);
        //只识别菜单属性的节点
        foreach ($list as $key => $module) {
            foreach ($module as $_key => $action) {
                foreach ($action as $__key => $method) {
                    if (!isset($method['menu']) || !is_array($method['menu']) || empty($method['menu']['status'])) {
                        unset($list[$key][$_key][$__key]);
                    }
                }
            }
        }
        return $list;
    }

}
