<?php

namespace Store\Controller;

/**
 * 店铺管理控制器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 14:03:09
 */
class TixianController extends StoreController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '会员提现列表';

    /**
     * 设置可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '会员提现列表',
        'tixianexamine' => '会员提现审核',
		'details' => '详细信息',
        'edit' => '编辑信息',
    );

    /**
     * 设置可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
		'index' => '会员提现列表',
		'tixianexamine' => '会员提现审核',
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'Tixian_record';

    /**
     * 首页列表前置方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
	public function index() {
		$this->assign('ptitle', '会员提现列表');
        $this->_bind_model = 'Tixian_record';
        parent::index();
    }
	
	protected function _index_filter(&$model, &$map) {
		$map['tr.member_id']  = array('neq',"");
    }
	
	protected function _index_list_filter(&$model , &$map) {   
        $model->field('tr.* ,m.nickname')
              ->alias('tr')
			  ->where($map)
			  ->join('left join __MEMBER__ m on tr.member_id = m.id');
    }
	
	protected function _index_data_filter(&$data, &$model) { 
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
	
    //提现审核
	public function tixianexamine() {
		$this->ptitle = "会员提现审核";
		$this->assign('ptitle', $this->ptitle);
        $this->_bind_model = 'Tixian_record';
        parent::index();
	}
	
	protected function _tixianexamine_filter(&$model, &$map) {
		$map['tr.status'] = 3 ;
		$map['tr.member_id']  = array('neq',"");
    }
	
	protected function _tixianexamine_list_filter(&$model , &$map) {   
        $model->field('tr.* ,m.nickname')
              ->alias('tr')
              ->where($map)
			  ->join('left join __MEMBER__ m on m.id = tr.member_id');
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
			$member_id = I('post.member_id');
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
					$member = M('Member')->where(array('id' => $member_id))->find();
					$myinfo['tixianprice'] = $member['tixianprice'] + $price ;
					$myinfo['create_date'] = date("Y-m-d H:i:s");
					M('Member')->where(array('id' => $member_id))->save($myinfo);
					
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
			$this->ptitle = "会员提现审核";
			$this->assign('ptitle', $this->ptitle);
			$this->assign('data', $data);
		}
	}
}
