<?php

namespace Store\Controller;

/**
 * 产品分类管理管制器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 14:01:00
 */
class CreditCatController extends StoreController {

    /**
     * 设置模块名称
     * @var type 
     */
    public $ptitle = '积分商品分类管理';

    /**
     * 设定可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '产品分类列表',
        'add' => '添加产品分类',
        'edit' => '编辑产品分类',
        'del' => '删除产品分类',
        'resume' => '启用产品分类',
        'forbid' => '禁用产品分类',
    );

    /**
     * 设定可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '产品分类列表'
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'StoreCreditProductCat';

    /**
     * 产品分类编辑前置方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:03:58
     */
    protected function _form_filter(&$model, &$data) {
        $map = array();
        $map['status'] = 2;
        $map['id'] = array('neq', I('get.id', 0, 'intval'));
//        $map['api_id'] = $this->api['id'];
        $catList = M('StoreCreditProductCat')->where($map)->select();
        array_unshift($catList, array('id' => 0, 'pid' => -1, 'name' => '请选择产品分类'));
        $this->catList = get_select_tree($catList);
    }

    /**
     * 首页列表前置方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    public function _before_index() {
        $this->ptitle = '产品分类管理';
    }

    /**
     * 数据过滤处理
     * 
     * @param type $list
     * @param type $model
     */
    protected function _index_data_filter(&$list) {
        $list = get_select_tree($list);
        foreach ($list as &$vo) {
            $vo['name'] = $vo['spl'] . $vo['name'];
        }
    }

}
