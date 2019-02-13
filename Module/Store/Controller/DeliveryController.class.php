<?php

namespace Store\Controller;

/**
 * 商品运费管理
 *
 * @author tanglinjun
 * @date 2014-12-17
 */
class DeliveryController extends StoreController {

    /**
     * 指定模块标题
     * @var type 
     */
    public $ptitle = '快递管理';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index' => '快递列表',
        'add' => '添加快递',
        'edit' => '编辑快递',
        'del' => '删除快递',
        'resume' => '启用快递',
        'forbid' => '禁用快递',
    );

    /**
     * 定义可设置快递的节点
     * @var type 
     */
    public $menu = array(
        'index' => '快递列表',
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'StoreDelivery';

    protected function _index_filter(&$model, &$map) {
        //祛除api_id的条件
//        unset($map['api_id']);
    }

}
