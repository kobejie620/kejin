<?php

namespace Guide\Controller;


class DaogouController extends GuideController {
    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'Guide';

    /**
     * 设置标题
     * @var type 
     */
    public $ptitle = '导购员列表';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index'  => '导购员列表',
		'examine' => '导购员审核',
		'tixian' => '导购员提现列表',
		'tixianexamine' => '导购员提现审核',
		'edit' => '详细信息',
		'details' => '详细信息',
        'del' => '删除导购员',
    );

    /**
     * 定义可设置门店的节点
     * @var type 
     */
    public $menu = array(
        'index' => '导购员列表',
		'examine' => '导购员审核',
		'tixian' => '导购员提现列表',
		'tixianexamine' => '导购员提现审核',
    );
	
	public function index() {
		$this->assign('ptitle', '导购员列表');
        $this->_bind_model = 'Guide';
        parent::index();
    }
	
	/**
     * 首页列表前置方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    protected function _index_filter(&$model, &$map) {
        //获取该门店的信息
        $user = session('user');
		if($user['role'] == '24') {      //門店或者admin
            $role = 0; 
			$map['store_num'] = $user['username'];
        }
		if($user['role'] == '1') {
			$role = 0; 
		}
		$this->assign('role', $role);
    }
	
	protected function _index_list_filter(&$model) { 
        $model->field('g.* , s.name , s.number')
              ->alias('g')
              ->where(array('g.type' => 3))
			  ->join('left join __STORES__ s on s.number = g.store_num');
    }

    protected function _index_data_filter(&$data) {
    }
	
	/**
	 * 詳細
	 * @param type $model
	 * @param type $data
	 */
	public function _form_filter(&$model, &$data) {
		if (IS_POST) {
			$submit = I('post.submit');
			$id = I('post.aduit_id');
			$store_num = I('post.store_num');
			if ($submit == '审核通过') {
				while (true) {
					$num = rand(1000, 10000);
					$guide_num = $store_num . $num  ;
					$result = M('Guide')->where(array('guide_num' => $guide_num))->find();
					if(!$result) {
						//生成导购员编号
						$info['guide_num'] = $guide_num;
						$info['create_date'] = date("Y-m-d H:i:s");
						$info['type'] = 3 ;
						break;
					}
				}
				
				$res = M('Guide')->where(array('id' => $id))->save($info); 
				if($res) {
					$this->success("审核通过");
				} else {
					$this->success("审核失败");
				}
			} else if ($submit == '审核不通过') {
				$info['type'] = 2 ;
				$info['create_date'] = date("Y-m-d H:i:s");
				$res = M('Guide')->where(array('id' => $id))->save($info); 
				if($res) {
					$this->success("拒绝通过成功");
				} else {
					$this->success("拒绝通过失败");
				}
			}
		} else {
			$this->ptitle = "门店审核";
			$this->assign('ptitle', $this->ptitle);
		}
	}
	
	//导购员提现列表
	public function tixian() {
		$this->ptitle = "导购员提现";
		$this->assign('ptitle', $this->ptitle);
        $this->_bind_model = 'Tixian_record';
        parent::index();
	}
	
	protected function _tixian_filter(&$model, &$map) {
		$map['tr.guide_num']  = array('neq',"");
        //获取该门店的信息
//        $user = session('user'); 
//		if($user['role'] == '24') {      //門店
//            $role = 0; 
//			$map['g.store_num'] = $user['number'] ;
//        }
//		$map['g.type'] = 1 ;
//		$this->assign('role', $role);
    }
	
	protected function _tixian_list_filter(&$model , &$map) {   
        $model->field('tr.* ,g.guide_name')
              ->alias('tr')
              ->where($map)
			  ->join('left join __GUIDE__ g on g.guide_num = tr.guide_num');
    }
	
	protected function _tixian_data_filter(&$data, &$model) { 
		foreach($data as &$val) {
			switch ($val['status']) {
				case 1:
					$val['status'] = "未提现";
					break;
				case 2:
					$val['status'] = "已提现";
					break;
				case 3:
					$val['status'] = "等待审批";
					break;
				case 4:
					$val['status'] = "申请被驳回";
					break;
				case 5:
					$val['status'] = "退款取消的提现";
					break;
				case 6:
					$val['status'] = "等待订单完成的备用状态";
			}	
		}
	}

	//导购员提现审核
	public function tixianexamine() {
		$this->ptitle = "导购员提现审核";
		$this->assign('ptitle', $this->ptitle);
        $this->_bind_model = 'Tixian_record';
        parent::index();
	}
	
	protected function _tixianexamine_filter(&$model, &$map) {
		$map['tr.status'] = 3 ;
		$map['tr.guide_num']  = array('neq',"");
    }
	
	protected function _tixianexamine_list_filter(&$model , &$map) {   
        $model->field('tr.* ,g.guide_name')
              ->alias('tr')
              ->where($map)
			  ->join('left join __GUIDE__ g on g.guide_num = tr.guide_num');
    }

	//导购员审核
	public function examine() {
		$this->ptitle = "导购员审核";
		$this->assign('ptitle', $this->ptitle);
        $this->_bind_model = 'Guide';
        parent::index();
	}
	
	protected function _examine_filter(&$model, &$map) {
        //获取该门店的信息
        $user = session('user'); 
		if($user['role'] == '24') {      //門店
            $role = 0; 
			$map['g.store_num'] = $user['username'] ;
        }
		$map['g.type'] = 1 ;
		$this->assign('role', $role);
    }
	
	protected function _examine_list_filter(&$model , &$map) {   
        $model->field('g.* , s.name ,s.number')
              ->alias('g')
              ->where($map)
			  ->join('left join __STORES__ s on s.number = g.store_num');
    }
	
	/*
	 * 提现详细
	 */
	public function details() {
        parent::index();
	}
	
	public function _details_filter(&$model, &$data) {
		if (IS_POST) {
			$submit = I('post.submit');
			$id = I('post.aduit_id');
			$price = I('post.price');
			$guide_num = I('post.guide_num');
			if ($submit == '审核通过') {
				$info['create_date'] = date("Y-m-d H:i:s");
				$info['status'] = 2 ;	
				$res = M('Tixian_record')->where(array('id' => $id))->save($info); 
				if($res) {
					/* 开始发红包 */
					$grabred = new \Wechat\Model\WechatRedPacketModel();
					$guide = M('Guide')->where(array('guide_num' => $guide_num))->find();
					$openid = $guide['openid'];
					$send_name = '精典妆家';
					$total_amount = $price;
					$wishing = '恭喜你获得精典妆家提现红包';
					$act_name = '提现红包';
					$remark = '提现红包';
					$type = '1';
					$type_id = 'NORMAL';
					$info = $grabred->grantRedPocket($openid, $send_name, $total_amount, $wishing, $act_name, $remark, $type, $type_id);
					$this->ajaxReturn($info);
					$this->success("审核通过");
				} else {
					$this->success("审核失败");
				}
			} else if ($submit == '审核不通过') {
				$info['status'] = 4 ;
				$info['create_date'] = date("Y-m-d H:i:s");
				$res = M('Tixian_record')->where(array('id' => $id))->save($info); 
				if($res) {
					//将金额打回给导购员
					$guide = M('Guide')->where(array('guide_num' => $guide_num))->find();
					$myinfo['tixianprice'] = $guide['tixianprice'] + $price ;
					$myinfo['create_date'] = date("Y-m-d H:i:s");
					M('Guide')->where(array('guide_num' => $guide_num))->save($myinfo);
					
					$info['create_date'] = date("Y-m-d H:i:s");
					$info['status'] = 4 ;	
					$res = M('Tixian_record')->where(array('id' => $id))->save($info); 
					$this->success("拒绝通过成功");
				} else {
					$this->success("拒绝通过失败");
				}
			}
		} else {
			$id = I('get.id');
			$data = M('Tixian_record')->where(array('id' => $id))->find();
//			$this->_bind_model = 'Tixian_record';
			$this->ptitle = "导购员提现审核";
			$this->assign('ptitle', $this->ptitle);
			$this->assign('data', $data);
		}
	}

}
