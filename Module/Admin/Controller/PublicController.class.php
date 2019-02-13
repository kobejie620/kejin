<?php

namespace Admin\Controller;

use Library\Util\Rbac;
use Think\Controller;

/**
 * 后台系统管理公共基类
 * 
 * @access public
 * @author zoujingli <zoujingli@qq.com>
 * @update 2014/07/04 23:58
 */
C('SHOW_PAGE_TRACE', false);
C('SHOW_ERROR_MSG', false);

class PublicController extends Controller {

    /**
     * 用户登录操作
     * 
     * @access public
     */
    public function login() {
        /* 如果用户已经登录，直接跳转到后台的入口页 */
        if (is_login()) {
            if (IS_AJAX) {
                $this->success('已经登录过了，请选择退出后再来登录！');
            } else {
                redirect(get_referer_url(U('Admin/Index/index', '', true, true)));
            }
            die();
        }

        if (IS_POST || IS_AJAX) {
            /* 执行登录 */
            if (Rbac::checkLogin(I('post.username'), I('post.password'))) {
                $url = U('Admin/Index/index');
                $this->success('登录成功，正在进入用户界面...', get_referer_url($url));
            } else {
                parent::error('登录失败，请稍候再试...');
            }
        } else {
            /* 加载网站配置数据 */
            $this->site = get_sysconfig();
            $this->assign('ptitle', '用户登录');
            $this->display();
        }
    }

    /**
     * 用户登出操作
     * @access public
     */
    public function loginOut() {
        session('user', null);
        session_destroy();
        $this->success('退出登录成功！', U('Admin/Public/login'));
    }

    /**
     * 显示错误页面
     */
    public function error() {
        $this->display();
    }

}
