<?php

namespace Fenxiao\Controller;


class JifenController extends FenxiaoController {
    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'Jifen_set';

    /**
     * 设置标题
     * @var type 
     */
    public $ptitle = '积分配置';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index'  => '积分配置',
		'yongjin' => '佣金设置',
		'commission' => '佣金比例',
		'add'    => '添加积分配置',
		'edit' => '修改积分配置',
        'del' => '删除积分配置',
        'resume' => '启用',
        'forbid' => '禁用',
		
    );

    /**
     * 定义可设置门店的节点
     * @var type 
     */
    public $menu = array(
        'index' => '积分配置',
    );
	
	public function index() {
		$this->assign('ptitle', '积分配置');
        $this->_bind_model = 'Jifen_set';
        parent::index();
    }
	
	/**
     * 首页列表前置方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    protected function _index_filter(&$model, &$map) {
        
    }

    protected function _index_data_filter(&$data) {
        
    }

}
