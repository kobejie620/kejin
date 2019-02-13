<?php

namespace Site\Controller;

class CatController extends SiteController {

    protected $_bind_model = 'SiteContentCat';

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '分类管理';

    /**
     * 设置模块可访问的操作
     * @var type 
     */
    public $access = array(
        'index'  => '内容分类列表',
        'edit'   => '编辑内容分类',
        'add'    => '添加内容分类',
        'del'    => '删除内容分类',
        'resume' => '启用内容分类',
        'forbid' => '禁用内容分类',
    );

    /**
     * 设定可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '分类列表',
    );

    /**
     * 表单前置处理方法
     */
    protected function _form_filter(&$model, &$data) {
        if (!IS_POST) {
            $list = (array) $model->where(array('status' => '2'))->select();
            $list[] = array('title' => '顶级分类', 'id' => 0, 'pid' => -1);
            $list = get_select_tree($list);
            //去除自己的子项，防止递归循环
            if (!empty($data['id'])) {
                $sub_ids = get_array_tree_lower_ids($list, $data['id']);
                foreach ($list as $key => $value) {
                    if (in_array(intval($value['id']), $sub_ids)) {
                        unset($list[$key]);
                    }
                }
            }
            $this->plist = $list;
        }
    }

    protected function _data_filter(&$data) {
        $data = get_select_tree($data);
        foreach ($data as &$row) {
            $row['title'] = $row['spl'] . $row['title'];
            $row['id'] = join(get_array_tree_lower_ids($data, $row['id']), ',');
            if (intval($row['is_show']) === 2) {
                $row['is_show'] = '显示';
            } else {
                $row['is_show'] = '不显示';
            }
        }
    }

}
