<?php

namespace Store\Controller;

/**
 * 店铺管理控制器
 * 
 * @author zenglingwen <630118900@qq.com>
 * @date 2014/09/04 14:03:09
 */
class XsconfigController extends StoreController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '抢购系统配置';

    /**
     * 设置可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '配置信息',
        'edit' => '编辑信息',
    );

    /**
     * 设置可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '抢购配置信息'
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'Store';
    protected function _index_filter(&$model, &$map) {
        $map['id'] = 1;
    }

    protected function _index_data_filter(&$data) {
        //dump($data);
        $this->vo = $data[0];
    }
    protected function _form_filter($model,$vo) {
        if(IS_POST){
            
        }
    }

}
