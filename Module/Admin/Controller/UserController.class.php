<?php

namespace Admin\Controller;

/**
 * 系统用户管理控制器
 */
class UserController extends AdminController {

    /**
     * 模块标题
     * @var type 
     */
    public $ptitle = '系统用户';

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'SysUser';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index'    => '用户列表',
        'add'      => '添加用户',
        'edit'     => '修改资料',
        'password' => '修改密码',
        'del'      => '删除用户',
        'resume'   => '启用用户',
        'forbid'   => '禁用用户',
    );

    /**
     * 定义可设置菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '用户列表',
    );

    /**
     * 数据显示过滤
     * 
     * @param type $model
     * @param array $map
     */
    protected function _index_filter(&$model, &$map) {
        if (!empty($map['username'])) {
            $map['username'] = array(array('neq', 'admin'), $map['username'], 'and');
        } else {
            $map['username'] = array('neq', 'admin');
        }
    }
    
    /**
     * from输出的前置操作 by zenglingwen
     */
    protected function _form_filter(&$model,&$vo) {

    }

    /**
     * 关联查询角色信息
     * @param \Think\Model $model
     * @param \array $map
     */
    protected function _index_list_filter(&$model) {
        $role_table = M('SysRole')->getTableName();
        $model->alias('a')
            ->field('a.*,b.name "b.name",b.group "b.group"')
            ->join("left join {$role_table} b on b.id=a.role");
    }

    protected function _add_filter() {
        $this->_edit_filter();
    }

    protected function _edit_filter() {
        if (!IS_POST) {
            $this->rolelist = D('SysRole')->where(array('status' => 2))->select();
        } else {
            if (empty($_POST['password'])) {
                unset($_POST['password']);
            } else {
				$password = I('post.password', '', 'md5');
				$re_password = I('post.re_password', '', 'md5');
				 //check new password
				if ($password != $re_password) {
					$this->error('新密码与确认密码不一致！');
				}
                $_POST['password'] = $password;
            }
        }
    }

    /**
     * 修改用户密码
     */
    public function password() {
        if (IS_POST) {
            $id = I('post.id', '', 'trim');
            $date['remark'] = I('post.remark', '', 'trim');
            $date['password'] = I('post.password', '', 'md5');
            $old_password = I('post.old_password', '', 'md5');
            $re_password = I('post.re_password', '', 'md5');
            //check old password
            $check = M('SysUser')->where(array('id' => $id, 'password' => $old_password))->find();
            if (!$check) {
                $this->error('旧密码不正确！');
            }
            //check new password
            if ($date['password'] != $re_password) {
                $this->error('新密码与确认密码不一致！');
            }
            $result = M('SysUser')->where(array('id' => $id))->save($date);
            if (false !== $result) {
                $this->success('密码修改成功！');
            } else {
                $this->error('密码修改失败，请稍候再试！');
            }
        } else {
            $id = I('get.id', '', 'trim');
            $vo = M('SysUser')->field('id, username')->where(array('id' => $id))->find();
            $this->assign('ptitle', '编辑用户信息');
            $this->assign('vo', $vo);
            $this->display();
        }
    }

}
