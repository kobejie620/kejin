<?php

namespace Admin\Controller;

use Library\Util\Node;

/**
 * 角色管理控制器
 * 
 * @author anyon <zoujingli@qq.com>
 */
class RoleController extends AdminController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '系统角色';

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'SysRole';

    /**
     * 关闭分页
     * @var type 
     */
    protected $_page_on = false;

    /**
     * 定义可访问的操作
     * @var type 
     */
    public $access = array(
        'index'  => '角色列表',
        'forbid' => '禁用角色',
        'resume' => '启用角色',
        'add'    => '添加角色',
        'edit'   => '修改角色',
        'del'    => '删除角色',
        'auth'   => '角色授权',
    );

    /**
     * 定义可设置菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '角色列表',
    );

    /**
     * 添加角色的前置方法
     */
    public function _before_add() {
        if (!IS_POST) {
            $_REQUEST['pid'] = I('get.id');
            unset($_GET['id'], $_REQUEST['id']);
        }
    }

    protected function _form_filter(&$model, &$data) {
        $data['group_list'] = M($this->_bind_model)->where(array('status' => 2))->select();
    }

    /**
     * 角色授权管理
     */
    public function auth() {
        $model = M('SysRoleNode');
        if (IS_POST) {
            $nodes = I('post.node');
            $role_id = I('get.id');
            $this->progress('正在解析权限节点数据...');
            $data = array();
            foreach ($nodes as $node) {
                $data[] = array('role_id' => $role_id, 'node' => strtoupper($node));
            }
            $model->startTrans();
            $this->progress('开始清理历史权限数据...');
            if (false !== $model->where(array('role_id' => $role_id))->delete()) {
                if (false !== $model->addAll($data, array(), true)) {
                    $model->commit();
                    $this->progress('授权信息保存成功', 1);
                }
            }
            $model->rollback();
            $this->progress('授权信息保存失败，请稍候再试', 0);
        } else {
            $action = I('get.action', '', 'trim');
            if ($action === 'getZtreeData') {
                $role_id = I('get.id');
                $checked = $model->where(array('role_id' => $role_id))->getField('node', true);
                $nodelist = Node::getNodeArrayTree(get_dir(APP_PATH));
                unset($nodelist['Admin']['Public'], $nodelist['Admin']['Index']);
                $data = array();
                $this->_genTableTree($this->_mergeNodeCheckStatus($nodelist, $checked), $data);
                $this->ajaxReturn($data);
            } else {
                $this->assign('ptitle', '角色授权');
                $this->display();
            }
        }
    }

    /**
     * 编译表格树
     * @param type $list
     * @param type $data
     * @param type $pitem
     * @param type $gitem
     */
    protected function _genTableTree($list, &$data = array(), &$pitem = array(), &$gitem = array()) {
        foreach ($list as $key => $vo) {
            $item = array();
            $item['name'] = $key;
            empty($pitem) || $item['open'] = true;
            if (isset($vo['ptitle']) && isset($vo['title']) && isset($vo['checked'])) {
                $item['name'] = $vo['title'];
                $item['checked'] = $vo['checked'];
                $item['path'] = $vo['path'];
                isset($pitem['name']) && $pitem['name'] = empty($vo['ptitle']) ? $pitem['name'] : $vo['ptitle'];
                isset($gitem['name']) && $gitem['name'] = empty($vo['gtitle']) ? $gitem['name'] : $vo['gtitle'];
            } else {
                is_array($vo) && $this->_genTableTree($vo, $item['children'], $item, $pitem);
            }
            $data[] = $item;
        }
    }

    /**
     * 合并节点的选中状态
     * 
     * @param type $node
     * @param type $checked
     * @return type
     */
    protected function _mergeNodeCheckStatus($node, $checked) {
        foreach ($node as $m => &$mm) {
            foreach ($mm as $c => &$cc) {
                foreach ($cc as $a => &$aa) {
                    $path = strtoupper($m . '/' . $c . '/' . $a);
                    is_array($aa) || $aa = array();
                    $aa['path'] = $path;
                    $aa['value'] = $path;
                    $aa['checked'] = in_array($path, $checked) ? true : false;
                }
            }
        }
        return $node;
    }


    /**
     * 删除过滤操作
     * @param $model
     * @param $ids
     * @return bool
     */
    protected function _del_filter(&$model, $ids) {
        $map = array();
        $map['id'] = array('in', (array) $ids);
        $nums = M('SysUser')->where($map)->count();
        if ($nums > 0) {
            $this->error("角色下还有{$nums}个用户，请将用户移走再来操作!");
            return false;
        }
    }

}
