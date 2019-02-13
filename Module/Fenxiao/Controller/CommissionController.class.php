<?php

namespace Fenxiao\Controller;


class CommissionController extends FenxiaoController {
    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'Yongjin_set';

    /**
     * 设置标题
     * @var type 
     */
    public $ptitle = '佣金设置';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index'  => '佣金设置',
    );

    /**
     * 定义可设置门店的节点
     * @var type 
     */
    public $menu = array(
        'index'  => '佣金设置',
    );
	
	public function index() {
		$info = M('Yongjin_set')->find();
        $this->assign("ptitle","佣金比例配置");
        $this->assign('info',$info);
        $this->display();
    }
	
	/**
     * 更新佣金设置
     */
    public function set() {
        if(IS_POST){
            $data = I('post.');
            $info = array();
            $info['xiaofei_com'] = $data['xiaofei_com'];
            $info['sg_com'] = $data['sg_com'];
			$info['store_com'] = $data['store_com'];
			$info['create_date'] = date("Y-m-d H:i:s");
			$res = M('Yongjin_set')->where(array('id' =>'1'))->find();
			if($res) {
				$result = M('Yongjin_set')->where(array('id' =>'1'))->save($info); 
				if($result){
					$this->success('更新成功');
				}else{
				  $this->success('更新成功');          
				}
			} else {
				M('Yongjin_set')->add($info); 
			}
			
        } 
    }

}
