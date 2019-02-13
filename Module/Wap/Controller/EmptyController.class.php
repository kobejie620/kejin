<?php

namespace Wap\Controller;

/**
 * 模块默认控制器
 */
class EmptyController extends WapController {

    /**
     * 访问拦截
     */
    public function _initialize() {
        $allow = array('oauth', 'setshare');
        if (!in_array(strtolower(ACTION_NAME), $allow)) {
            parent::_initialize();
            exit($this->_empty());
        }
    }

    /**
     * 默认操作
     */
    protected function _empty() {
        $map = array();
        $map['status'] = 2;
        $map['code'] = strtolower(CONTROLLER_NAME);
        $data = M('SitePage')->where($map)->find();
        if (empty($data)) {
            R('Error/show');
        }
        /* 微信网页授权处理 */
        if (intval($data['is_wechat_auth']) === 2 || intval($this->wapsite['is_wechat_auth']) === 2) {
            /* 分享有礼 */
            if (!empty($data['integral']) && intval($this->wapsite['page_share_integral']) === 2) {
                $this->assign('share_integral', true);
            }
            $this->oauth();
        }
        /* 处理点击数 */
        $view_click_key = 'page_click_' . $data['id'];
        if (!session($view_click_key)) {
            session($view_click_key, true);
            M('SitePage')->where($map)->setInc('click', 1);
        }
        /* 处理重定向 */
        if (!empty($data['url'])) {
            redirect($data['url']);
        }
        $data['modules'] = explode('##', $data['module_ids']);
        $this->assign('ptitle', $data['title']);
        $this->assign('data', $data);
        $this->display(T('Wap@Empty:index'));
        die();
    }

}
