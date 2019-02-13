<?php

namespace Site\Controller;

class ModuleController extends SiteController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '模块管理';

    /**
     * 设置模块可访问的操作
     * @var type 
     */
    public $access = array(
        'index'  => '模块列表',
        'edit'   => '编辑模块',
        'add'    => '添加模块',
        'del'    => '删除模块',
        'resume' => '启用模块',
        'forbid' => '禁用模块',
    );

    /**
     * 定义可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '模块列表',
    );

    /**
     * 绑定控制器的模型名称
     * @var type
     */
    protected $_bind_model = "SitePageModule";

    /**
     * 绑定类型
     * @var type 
     */
    protected $types = array(
        'slider' => '轮播图组件',
        'links'  => '链接面板',
    );

    protected function _data_filter(&$data) {
        foreach ($data as &$vo) {
            $vo['type'] = $this->types[$vo['type']];
        }
    }

    /**
     * 显示添加类型选择
     * @return boolean
     */
    protected function __add_filter() {
        $type = I('get.type', '', 'trim');
        if (!IS_POST) {
            if (!isset($this->types[$type])) {
                $this->assign('types', $this->types);
                $this->assign('ptitle', '选择模块类型');
                $this->display('add');
                return false;
            }
        }
    }

    /**
     * 添加成功处理
     * @param type $model
     * @param type $data
     */
    protected function _add_success($model, $data) {
        if (IS_POST) {
            $url = U('Site/Model/edit');
            if (auth('Site/Module/edit')) {
                $url = U('Site/Module/edit', array('id' => $data['id']));
            }
            $this->success('添加模块成功!', $url);
        }
    }

    /**
     * 表单方法过滤操作
     */
    protected function _form_filter($model, $data) {
        if (!IS_POST) {
            set_select_menu('Site/Module/index');
            if (isset($this->types[$data['type']])) {
                $this->assign('type_name', $this->types[$data['type']]);
            }
            $this->assign('types', $this->types);
            $this->assign('ptitle', $this->ptitle);
            $this->assign('vo', $data);
            $this->display($data['type']);
            return false;
        }
    }

}
