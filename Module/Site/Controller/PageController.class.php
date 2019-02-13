<?php

namespace Site\Controller;

class PageController extends SiteController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '页面管理';

    /**
     * 设置模块可访问的操作
     * @var type 
     */
    public $access = array(
        'index'  => '页面列表',
        'edit'   => '编辑',
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
        'index' => '页面列表',
    );

    /**
     * 绑定控制器的模型名称
     * @var type
     */
    protected $_bind_model = "SitePage";

    /**
     * 表单前置方法
     * @param type $model
     * @param type $data
     */
    protected function _form_filter($model, &$data) {
        if (IS_POST) {
            $data['module_ids'] = join('##', I('post.module', ''));
            $data['is_show_title'] = I('post.is_show_title', '1', 'trim,intval');
            $data['is_show_link'] = I('post.is_show_link', '1', 'trim,intval');
        } else {
            $modules = M('SitePageModule')->order('sort asc,id desc')->select();
            if (isset($data['module_ids'])) {
                $modules_checked = explode('##', $data['module_ids']);
                foreach ($modules as &$vo) {
                    in_array($vo['id'], $modules_checked) && $vo['checked'] = 'true';
                }
            }
            $this->assign('modules', $modules);
        }
    }

    /**
     * 数据列表过滤
     * @param type $data
     */
    protected function _data_filter(&$data) {

        foreach ($data as &$vo) {
            if (empty($vo['integral'])) {
                $vo['integral'] = '未设置积分数';
            }
            if (intval($vo['is_wechat_auth']) !== 2 && intval($this->wapsite['is_wechat_auth']) !== 2) {
                $vo['integral'] = '未开启网页授权';
            }
            if (intval($this->wapsite['page_share_integral']) !== 2) {
                $vo['integral'] = '已关闭积分';
            }
            $url = U('/wap/' . $vo['code'], '', true, true);
            $vo['code'] = "<a href='{$url}' target='_blank'>访问页面</a> " .
                    " <a class='fr' href='javascript:void(0)' title='复制链接' data-copy='{$url}'><i class='glyphicon glyphicon-copy'></i></a>";
        }
    }

    /**
     * 数据提交成功后的回调 尝试更新关键字
     * 
     * @param \Think\Model $model
     * @param array $data
     */
    protected function _form_success($model, $data) {
        $data['url'] = U('/wap/' . $data['code']);
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
