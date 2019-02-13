<?php

namespace Store\Controller;

/**
 * 产品规格管理操作器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 14:02:22
 */
class CreditModelController extends StoreController {

    /**
     * 设置模块名称
     * @var type 
     */
    public $ptitle = '积分商品规格管理';

    /**
     * 设定可访问的操作
     * @var type 
     */
    public $access = array(
        'index'  => '产品规格列表',
        'add'    => '添加产品规格',
        'edit'   => '编辑产品规格',
        'del'    => '删除产品规格',
        'resume' => '启用产品规格',
        'forbid' => '禁用产品规格',
    );

    /**
     * 设定可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '产品规格列表',
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'StoreCreditProductModel';

    /**
     * 首页列表数据处理
     * 
     * @author tanglinjun
     * @date 2014-11-19
     */
    protected function _index_data_filter(&$data) {
        //产品规格详情
        foreach ($data as &$val) {
            $val['desc'] = '';
            $params = json_decode($val['params'], true);
            foreach ($params as $sval) {
                $pname = $sval['name'];
                $pvalue = $sval['value'];
                $val['desc'] .= $pname . ":" . $pvalue . '<br/>';
            }
        }
    }

    /**
     * 表单前置处理方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 16:20:21
     */
    protected function _form_filter(&$model, &$data) {
        if (IS_POST) {
            $names = I('post.names');
            $types = I('post.types');
            $values = I('post.values');
            $_data = array();
            foreach ($names as $key => $name) {
                if (!empty($name)) {
                    $_data[] = array(
                        'name'  => $name,
                        'type'  => $types[$key],
                        'value' => str_replace('，', ',', $values[$key])
                    );
                }
            }
            $data['params'] = json_encode($_data);
        }
    }

}
