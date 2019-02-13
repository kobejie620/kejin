<?php

namespace Wap\Controller;

/**
 * 内容显示控制器
 */
class TextController extends WapController {

    /**
     * 显示文本内容
     */
    public function view() {
        $map = array();
        $map['id'] = I('get.id', '', 'trim,intval');
        $data = M('SiteContent')->where($map)->find();
        if (empty($data)) {
            R('Error/show');
        }
        /* 微信网页授权处理 */
        if (intval($data['is_wechat_auth']) === 2 || intval($this->wapsite['is_wechat_auth']) === 2) {
            //分享有礼
            if (!empty($data['integral']) && intval($this->wapsite['content_share_integral']) === 2) {
                $this->assign('share_integral', true);
            }
            $this->oauth();
        }
        /* 处理点击数 */
        $view_click_key = 'view_click_' . $data['id'];
        if (!session($view_click_key)) {
            session($view_click_key, true);
            M('SiteContent')->where($map)->setInc('click', 1);
        }
        /* 处理重定向 */
        if (!empty($data['url'])) {
            redirect($data['url']);
        }
        $this->assign('ptitle', $data['title']);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 分类更多列表
     */
    public function more() {
        $this->ptitle = '分类列表';
        $this->_bind_model = 'SiteContent';
        $this->cat = M('SiteContentCat')->find(I('get.cid', '0', 'trim,intval'));
        if (empty($this->cat)) {
            redirect(U('Wap/Text/cat'));
        } else {
            parent::index();
        }
    }

    /**
     * 显示文本分类
     */
    public function cat() {
        $this->_page_on = false;
        $this->ptitle = '内容分类';
        $this->_bind_model = 'SiteContentCat';
        parent::index();
    }

    /**
     * 分类查询过滤
     * @param type $modal
     * @param type $map
     */
    protected function _cat_filter(&$modal, &$map) {
        $map['status'] = '2';
        $map['pid'] = '0';
        $map['is_show'] = 2;
        $id = I('get.id', null, 'trim');
        empty($id) || $map['id'] = $id;
    }

    /**
     * 分类数据过滤
     * @param type $catList
     */
    protected function _cat_data_filter(&$catList) {
        foreach ($catList as &$cat) {
            $where = array();
            $where['cid'] = $cat['id'];
            $where['is_show'] = 2;
            $where['status'] = 2;
            $id = I('get.id', null, 'trim');
            $cat['more_class'] = 'hide';
            if ($id) {
                $cat['list'] = M('SiteContent')->field('id,cid,title,link,create_date')->where($where)->order('sort asc,id desc')->select();
            } else {
                $cat['list'] = M('SiteContent')->field('id,cid,title,link,create_date')->where($where)->order('sort asc,id desc')->limit(0, 6)->select();
                (count($cat['list']) > 5) && $cat['more_class'] = '';
            }
        }
    }

}
