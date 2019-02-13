<?php

namespace Admin\Controller;

use Library\Controller\Controller;
use Library\Util\Rbac;

/**
 * 后台系统管理公共基类
 * 
 * @RBAC权限控制
 * 
 * @access public
 * @author zoujingli <zoujingli@qq.com>
 * @update 2014/07/01 21:58
 */
class AdminController extends Controller {

    /**
     * 定义模块名称
     * @var type 
     */
    public $gtitle = '系统管理';

    /**
     * 设置可访问的方法名
     * @var type 
     */
    public $access = array(
        'index'  => '数据显示',
        'add'    => '添加数据',
        'edit'   => '编辑数据',
        'del'    => '删除数据',
        'forbid' => '禁用数据',
        'resume' => '启用数据',
    );

    /**
     * 模块初始化方法 这里需要做RBAC权限控制
     * 
     * @access protected
     * @author zoujingli <zoujingli@qq.com>
     * @update 2014/07/01 21:58
     */
    public function _initialize() {

        /* 用户登录检测 */
        $this->_checkLogin();

        /* RBAC 访问权限检测 */
        if (!Rbac::checkRule()) {
            $this->error('抱歉，没有访问此页面的权限！');
        }

        /* 如果非Ajax请求的页面，加载菜单和网站配置信息 */
        if (!IS_AJAX && !IS_POST) {
            /* 加载网站配置数据 */
            $this->site = get_sysconfig();
        }
    }

    /**
     * 检查用户是否已经登录
     * 没有登录则跳转到登录页面 同时记录来时的链接
     */
    protected function _checkLogin() {
        if (!is_login()) {
            set_referer_url();
            $login_url = U('Admin/Public/login');
            $script = "<script>window.top.location.href='{$login_url}';</script>";
            if (IS_AJAX) {
                $this->error("登录网关认证失败，请重新登录{$script}", $login_url);
            } else {
                ob_flush();
                echo $script;
                flush();
                redirect($login_url);
            }
        }
    }

}
