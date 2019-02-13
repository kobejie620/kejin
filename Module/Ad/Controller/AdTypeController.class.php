<?php

namespace Ad\Controller;

/**
 * 门店管理控制器
 *
 * @author tanglinjun
 * @date 2014-10-21
 */
class AdTypeController extends AdController {

    public $ptitle = '广告分类';

    /**
     * 定义可访问的方法名
     * @var type
     */
    public $access = array(
        'index' => '广告分类列表',
        'add' => '添加广告分类',
        'edit' => '编辑广告分类',
        'del' => '删除广告分类',
        'resume' => '启用广告分类',
        'forbid' => '禁用广告分类',
    );

    /**
     * 设定可设置为菜单的节点
     * @var type
     */
    public $menu = array(
        'index' => '广告分类列表'
    );

    /**
     * 绑定操作模型
     * @var type
     */
    protected $_bind_model = 'AdType';

    /**
     * 首页列表前置方法
     *
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    public function _before_index() {
        $this->ptitle = '广告分类管理';
    }

    /**
     * 列表显示过滤方法
     * @param type $model
     * @param type $map
     */
    protected function _index_filter(&$model, &$map) {
        //设置排序，按照订单生成时间，降序
        val('_order', 'type_id');
        val('_sort', 0);
    }


    /**
     * 产品分类编辑前置方法
     *
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:03:58
     */
    protected function _form_filter(&$model, &$data) {

    }
}
