<?php

namespace Site\Controller;

class ContentController extends SiteController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '内容管理';

    /**
     * 设置模块可访问的操作
     * @var type 
     */
    public $access = array(
        'index'  => '内容列表',
        'edit'   => '编辑内容',
        'add'    => '添加内容',
        'del'    => '删除内容',
        'resume' => '启用内容',
        'forbid' => '禁用内容',
    );

    /**
     * 定义可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '内容列表'
    );

    /**
     * 绑定控制器的模型名称
     * @var type
     */
    protected $_bind_model = "SiteContent";

    /**
     * 列表过滤方法
     */
    protected function _index_filter() {
        $this->catList = get_select_tree(M('SiteContentCat')->where(array('status' => 2))->select());
    }

    /**
     * 数据过滤操作
     * @param type $data
     */
    protected function _index_data_filter(&$data) {
        $wechat_auth = array(
            1 => '不认证',
            2 => '需认证'
        );
        foreach ($data as &$vo) {
            if (empty($vo['integral'])) {
                $vo['integral'] = '未设置积分数';
            }
            if (intval($vo['is_wechat_auth']) !== 2 && intval($this->wapsite['is_wechat_auth']) !== 2) {
                $vo['integral'] = '未开启网页授权';
            }
            if (intval($this->wapsite['content_share_integral']) !== 2) {
                $vo['integral'] = '已关闭积分';
            }
            if (intval($vo['is_show']) === 2) {
                $vo['is_show'] = '显示';
            } else {
                $vo['is_show'] = '不显示';
            }
            if (empty($vo['url'])) {
                $vo['url'] = U('Wap/Text/view', array('id' => $vo['id']));
                $vo['url'] = "<a target='_blank' href='{$vo['url']}'>系统内容</a>";
            } else {
                $vo['url'] = "<a target='_blank' href='{$vo['url']}'>指定链接</a>";
            }
        }
    }

    /**
     * 表单前置操作
     */
    protected function _form_filter(&$model, &$data) {
        if (IS_POST) {
            $data['is_show_title'] = I('post.is_show_title', '1', 'trim,intval');
            $data['is_show_link'] = I('post.is_show_link', '1', 'trim,intval');
        }
        $catlist = M('SiteContentCat')->where(array('status' => 2))->select();
        $this->catList = get_select_tree($catlist);
    }

    /**
     * 数据提交成功后的回调 尝试更新关键字
     * 
     * @param \Think\Model $model
     * @param array $data
     */
    protected function _form_success($model, $data) {
        if ($data['url']) {
            $data['url'] = to_domain($data['url']);
        } else {
            $data['url'] = U('Wap/Text/view', array('id' => $data['id']));
        }
        $this->_writeKeys($model, $data, 'news');
    }

    /**
     * 数据被删除后的回调 尝试删除关键字
     * 
     * @param \Think\Model $model
     * @param array $ids
     */
    protected function _del_success($model, $ids) {
        foreach ($ids as $id) {
            $data = array();
            $data['id'] = $id;
            $this->_writeKeys($model, $data, 'news');
        }
    }

}
