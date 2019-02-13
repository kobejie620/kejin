<?php

namespace Store\Controller;

/**
 * 店铺管理控制器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 14:03:09
 */
class ConfigController extends StoreController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '商城系统配置';

    /**
     * 设置可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '配置信息',
        'set' => '更新提现配置',
        'edit' => '编辑信息',
        'tixian' => '提现配置'
    );

    /**
     * 设置可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '配置信息',
        'tixian' => '提现配置',
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'Store';

    /**
     * 首页列表前置方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    protected function _index_filter(&$model, &$map) {
        $map['id'] = 1;
    }

    protected function _index_data_filter(&$data) {
        $this->vo = $data[0];
    }
    
    /**
     * 提现配置
     * @author chenrongbin
     */
    public function tixian() {
        $info = M('Store')->find();
        $this->assign("ptitle","提现配置");
        $this->assign('info',$info);
        $this->display();
    }
    
    /**
     * 更新提现配置
     */
    public function set() {
        if(IS_POST){
            $data = I('post.');
            $info = array();
            $info['number'] = $data['number'];
            $info['min_money'] = $data['min_money'];
            $result = M('Store')->where(array('id' =>'1'))->save($info); 
            if($result){
                 $this->success('更新成功');
                 
             }else{
               $this->success('更新成功');   
                 
             }
        } 
    }
}
