<?php

namespace Admin\Controller;

/**
 * 后台首页入口
 */
class IndexController extends AdminController {

    /**
     * 设置模块名称
     * @var type 
     */
    public $ptitle = '后台入口';

    /**
     * 首页入口
     */
    public function index() {
        //检查用户状态信息
        $this->ptitle = '系统信息';
        if (session('user.username') === 'admin') {
            $this->_main();
        }
        $this->assign('user', M('SysUser')->find(get_user_id()));
        $this->assign('ptitle', $this->ptitle);
        $this->display();
    }

    /**
     * 读取环境信息数据
     */
    protected function _main() {
        $list = array();
        $list['系统类型'] = PHP_OS;
        $list['PHP运行方式'] = php_sapi_name();
        $list['PHP/ZEND版本'] = PHP_VERSION . '/' . Zend_Version();
        $list['环境运行用户'] = get_current_user();
        $list['最大运行内存'] = get_cfg_var("memory_limit") ? get_cfg_var("memory_limit") : '-';
        $list['最大执行时间'] = get_cfg_var("max_execution_time") ? get_cfg_var("max_execution_time") . '秒' : '-';
        //GD库信息
        $gd = array('GD Version' => '<span style="color:red">未安装</span>');
        if (function_exists('gd_info')) {
            $gd_info = gd_info();
            $gd['GD Version'] = $gd_info['GD Version'];
        }
        $list['gd库版本'] = $gd['GD Version'];
        $list['curl函数库'] = function_exists('curl_init') ? '已加载' : '<span style="color:red">未安装</span>';
        $list['mcrypt函数库'] = function_exists('mcrypt_generic') ? "已加载" : '<span style="color:red">未安装</span>';
        $list['iconv版本'] = function_exists('iconv') ? "已加载" : '<span style="color:red">未安装</span>';

        $list['服务器域名'] = I('server.SERVER_NAME') . ':' . I('server.SERVER_PORT');
        $list['客户端IP'] = gethostbyname(I('server.REMOTE_ADDR'));
        $list['当前会话数'] = M()->table('think_session')->count();
        $list['当前登录帐号'] = session('user.username');
        $this->list = $list;
    }

}
