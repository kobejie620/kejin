<?php

namespace Wap\Controller;

/**
 * 导购员前台控制器
 * @author jindewen <jindewen@21cn.com>
 * @time 2015-03-24 18:00
 */
class GuideController extends WapController {

    protected $_bind_model = 'Guide';

    /**
     * 用户信息检测
     */
    public function _initialize() {
        parent::_initialize();
		$this->getMember($this->oauth(), null, false);

    }

    /**
     * 导购员申请
     */
    public function apply() {
		if(IS_POST) {
			$data['guide_name'] = I('post.name');
			$data['phone'] = I('post.phone');
			$data['province'] = I('post.province');
			$data['city'] = I('post.city');
			$data['street'] = I('post.street');
			$data['address'] = I('post.address');
			$data['store_name'] = I('post.store_name');
			$data['store_num'] = I('post.store_num');
			$data['openid'] = $this->openid;
			$data['create_date'] = date("Y-m-d H:i:s");
			
			//判断该导购员是否申请了
			$myresult = M('Guide')->where(array('openid' => $this->openid))->find();   
			if($myresult['type'] == 1) {
				$this->error("您已提交申请，请耐心等待结果!");
			} else if ($myresult['type'] == 3) {
				$this->error("您已经申请了导购员");
			} else {
				//判断门店编号是否正确
				$result = M('Stores')->where(array('number' => $data['store_num']))->find();
				if($result === null) {
					$this->error("请输入正确的门店编号");
				} else {
					//获取该用户的绑定门店
					$info = M('Member')
							->field('m.* , s.name')
							->alias('m')
							->join('left join __STORES__ s on s.id = m.sid')
							->where(array('m.openid' => $this->openid))
							->find();
					if($info['sid'] == null) {
						$data['type'] = 1 ;
						M('Guide')->add($data) ? $this->success('您已提交申请，请耐心等待结果!',U('Shop/index/index', '', true, true)) : $this->error('导购员失败，请稍后重试');
					}
					if((!empty($info['sid'])) && ($info['sid'] === $result['id'])) {
						if($result['name'] === $data['store_name']) {
							$data['type'] = 1 ;
							M('Guide')->add($data) ? $this->success('您已提交申请，请耐心等待结果!',U('Shop/index/index', '', true, true)) : $this->error('导购员失败，请稍后重试');
						} else{
							$this->error("门店编号或门店名称错误!");
						}
					} else {
						$this->error("你已经绑定了".$info['name']."门店,"."请输入对应的门店编号");
					}
				}
			}	
		}
        $this->assign('ptitle', '导购员申请');
        $this->display();
    }
	
	/*
	 * 导购员中心
	 */
	public function info() {
		//获取导购员的个人信息
		$map['openid'] = $this->openid ;
		$map['type'] = 3;
		$result = M('Guide')->where($map)->find();
		if(!$result) {
			$this->success("你还不是导购员，请先去申请!",U('Wap/Guide/apply', '', true, true));
		} else {
			$info = $this->getMember($this->oauth(), null, true);
			//获取导购员的积分信息
			$data = M('Member')->where(array('openid' => $this->openid))->find();
			$this->assign("guide",$result);
			$this->assign("info",$info);
			$this->assign("data",$data);
			$this->assign("ptitle","导购员中心");
			$this->display();
		}
		
	}
	
	/*
	 * 我的成绩
	 */
	public function score() {
		$info = $this->getMember($this->oauth(), null, true);
		$guide = M('Guide')->where(array('openid' => $this->openid))->find();
		$data = M('Guide_score')->where(array('openid' => $this->openid))->find();
		foreach ($data as &$val) {
			if(empty($val['fenxiang'])) {
				$val['fenxiang'] = 0 ;
			}
			if(empty($val['dianji'])) {
				$val['dianji'] = 0 ;
			}
		}
		//提成总额
		//我的提成记录总金额
		$ticheng = M('tixian_record')
				->field('sum(price) price')
				->where(array('guide_num' => $guide['guide_num'] ,'status' => 2))
				->find();
		$total_ticheng = $guide['tixianprice'] + $ticheng['price'];
		//用户购买次数
		$goumai = M('Guide_discount')->where(array('guide_num' => $guide['guide_num'] ,'status' => 1))->count();
//		$member = M('Member')->where(array('openid' => $this->openid))->find();
//		$goumai2 = M('Member_discount')->where(array('memberid' => $member['id']))->count();
//		$goumai = $goumai1 + $goumai2 ;
		//线下绑定
		$bangding = M('Guide_binding')->where(array('guide_num' => $guide['guide_num']))->count();
		//推荐关注
		$guanzhu = M('Promotion_user')->where(array('promotion_openid' => $this->openid))->count();
		$this->assign("bangding",$bangding);
		$this->assign("guanzhu",$guanzhu);
		$this->assign("goumai",$goumai);
		$this->assign("info",$info);
		$this->assign("data",$data);
		$this->assign("guide",$guide);
		$this->assign("total",$total_ticheng);
		$this->assign("ptitle","我的成绩");
        $this->display();
	}
	
	/**
	 * 推荐关注
	 */
	public function tuijian() {
		$this->getMember($this->oauth(), null, true);
        val('_sort', 'asc');
        val('_order', 'create_time');
		$this->_bind_model = 'Promotion_user';
        $this->ptitle = '推荐会员';
        parent::index();
	}
	
	/*
	 * 线下绑定记录过滤
	 */
	protected function _tuijian_filter($model, &$map) {
		$date = date("Y-m");
		$year = I('get.year', '', 'trim');
		$month = I('get.month', '', 'trim');
		if((!empty($year) && (!empty($month)))) {
			if($month < 10) {
				$date = $year . "-" . "0" . $month ;
			} else {
				$date = $year . "-" . $month ;
			}
			
		} 
		$map['pu.create_time'] = array("like",$date . "%");
//		else {
//			$map['pu.create_time'] = array("like",$date . "%");
//		}
		$map['pu.promotion_openid'] = $this->openid;
		$this->assign("date",$date);
    }
	
	public function _tuijian_list_filter(&$model, &$map) {
		$model->field('pu.create_time,m.nickname ,pu.status')
			  ->join('left join __MEMBER__ m on m.id = pu.uid')
			  ->alias('pu');
	}
	
	protected function _tuijian_data_filter(&$data, &$model) { 
		foreach($data as &$val) {
			switch ($val['status']) {
				case 1:
					$val['desc'] = "有效";
					break;
				case 2:
					$val['desc'] = "无效";
			}	
		}	
	}
	
	/**
	 * 线下绑定
	 */
	public function xianxiabinding() {
		$this->getMember($this->oauth(), null, true);
        val('_sort', 'asc');
        val('_order', 'create_date');
		$this->_bind_model = 'GuideBinding';
        $this->ptitle = '线下绑定记录';
        parent::index();
	}
	/*
	 * 线下绑定记录过滤
	 */
	protected function _xianxiabinding_filter($model, &$map) {
		$date = date("Y-m");
		$year = I('get.year', '', 'trim');
		$month = I('get.month', '', 'trim');
		if((!empty($year) && (!empty($month)))) {
			if($month < 10) {
				$date = $year . "-" . "0" . $month ;
			} else {
				$date = $year . "-" . $month ;
			}
			
		}
		$map['gb.create_date'] = array("like",$date . "%");
//		else {
//			$map['gb.create_date'] = array("like",$date . "%");
//		}
		$map['gb.openid'] = $this->openid;
		$this->assign("date",$date);
    }
	
	public function _xianxiabinding_list_filter(&$model, &$map) {
		$model->field('gb.create_date,gb.member_name ,gb.status')
			  ->alias('gb');
	}
	
	//线下绑定数据过滤
	protected function _xianxiabinding_data_filter(&$data, &$model) { 
		foreach($data as &$val) {
			switch ($val['status']) {
				case 1:
					$val['desc'] = "有效";
					break;
				case 2:
					$val['desc'] = "无效";
			}	
		}
	}
	
	/**
	 * 返利记录
	 */
	public function rebate() {
		$this->getMember($this->oauth(), null, true);
        val('_sort', 'asc');
        val('_order', 'create_time');
		$this->_bind_model = 'Guide_discount';
        $this->ptitle = '返利记录';
        parent::index();
	}
	
	/**
     * 返利记录过滤
     * @param type $model
     * @param array $map
     */
    protected function _rebate_filter($model, &$map) {
		//获取会员的信息
		$member = M('Member')->where(array('openid' => $this->openid))->find();
		//获取导购员的个人信息
		$mymap['openid'] = $this->openid ;
		$mymap['type'] = 3;
		$guide = M('Guide')->where($mymap)->find();
		if(!$guide) {
			$this->error("你还不是导购员，请先申请!");
		}
		$date = date("Y-m");
		$year = I('get.year', '', 'trim');
		$month = I('get.month', '', 'trim');
		if((!empty($year) && (!empty($month)))) {
			if($month < 10) {
				$date = $year . "-" . "0" . $month ;
			} else {
				$date = $year . "-" . $month ;
			}

		} 
		$map['d.create_time'] = array("like",$date . "%");
        $map['d.guide_num'] = $guide['guide_num'];
		$map['d.status'] = 1;

		//$map['d.guide_num|d.memberid'] =array($guide['guide_num'],$member['id'],'_multi'=>true);
		$this->assign("date",$date);
    }
	
	public function _rebate_list_filter(&$model, &$map) {
		$model->field('d.* , sop.product_name , so.accept_name')
			  ->join('left join __STORE_ORDER__ so on d.order_id = so.id')
			  ->join('left join __STORE_ORDER_PRODUCT__ sop on d.order_id = sop.order_id')
			  ->alias('d');  
		//p($model->getLastSql());
	}
	
	//返利记录数据过滤
//	protected function _rebate_data_filter(&$data, &$model) {
//		//获取member信息
//		$member = M('Member')->where(array('openid' => $this->openid))->find();
//		$date = date("Y-m");
//		$year = I('get.year', '', 'trim');
//		$month = I('get.month', '', 'trim');
//		if((!empty($year) && (!empty($month)))) {
//			if($month < 10) {
//				$date = $year . "-" . "0" . $month ;
//			} else {
//				$date = $year . "-" . $month ;
//			}
//		}
//		$map['d.create_time'] = array("like",$date . "%");
//        $map['d.memberid'] = $member['id'];
//		$map['d.status'] = 1;
//		
//		$info = M('Member_discount')
//				->field('d.* , sop.product_name , so.accept_name')
//				->join('left join __STORE_ORDER__ so on d.order_id = so.id')
//				->join('left join __STORE_ORDER_PRODUCT__ sop on d.order_id = sop.order_id')
//				->where($map)
//				->alias('d')
//				->select(); 
//		
//		foreach ($info as &$val) {
//			$val['create_time'] = substr($val['create_time'], 0 ,10) ;
//		}
//		$data = array_merge($data , $info) ;
//	}

		/**
	 * 提现记录
	 */
	public function tixianrecord() {
		$this->getMember($this->oauth(), null, true);
        val('_sort', 'asc');
        val('_order', 'create_date');
		$this->_bind_model = 'Tixian_record';
        $this->ptitle = '提现记录';
        parent::index();
	}
	
	/**
     * 提现记录过滤
     * @param type $model
     * @param array $map
     */
    protected function _tixianrecord_filter($model, &$map) {
		//获取member信息
		$member = M('Member')->where(array('openid' => $this->openid))->find();			
		$date = date("Y-m");
		$year = I('get.year', '', 'trim');
		$month = I('get.month', '', 'trim');
		if((!empty($year) && (!empty($month)))) {
			if($month < 10) {
				$date = $year . "-" . "0" . $month ;
			} else {
				$date = $year . "-" . $month ;
			}
		} 
		//获取该导购员的信息
		$guide = M('Guide')->where(array('openid' => $this->openid))->find();
		$map['tr.create_date'] = array("like",$date . "%");
        $map['tr.guide_num'] = $guide['guide_num'];
		//$map['tr.guide_num|member_id'] =array($guide['guide_num'],$member['id'],'_multi'=>true);
		$map['tr.status'] = 2 ;
		$this->assign("date",$date);
    }
	
	public function _tixianrecord_list_filter(&$model, &$map) {
		$model->field('tr.create_date , tr.price')
			  ->group('DATE_FORMAT(tr.create_date,\'%Y-%m-%d\')')
			  ->alias('tr');
	}

	/*
	 * 积分记录
	 */
	public function jifenrecord() {
		$this->getMember($this->oauth(), null, true);
        val('_sort', 'asc');
        val('_order', 'create_date');
		$this->_bind_model = 'MemberIntegral';
        $this->ptitle = '积分记录';
        parent::index();
	}
	
	/**
     * 积分记录过滤
     * @param type $model
     * @param array $map
     */
    protected function _jifenrecord_filter($model, &$map) {
		$date = date("Y-m");
		$year = I('get.year', '', 'trim');
		$month = I('get.month', '', 'trim');
		if((!empty($year) && (!empty($month)))) {
			if($month < 10) {
				$date = $year . "-" . "0" . $month ;
			} else {
				$date = $year . "-" . $month ;
			}
		} 
		$map['gi.create_date'] = array("like",$date . "%");
//		else {
//			$map['gi.create_date'] = array("like",$date . "%");
//		}
        $map['gi.openid'] = $this->openid;
		$this->assign("date",$date);
    }
	
	public function _jifenrecord_list_filter(&$model, &$map) {
		$model->field('gi.create_date,sum(gi.integral) integral ,gi.desc')
			  ->group('gi.desc ,DATE_FORMAT(gi.create_date,\'%Y-%m-%d\')')
			  ->alias('gi');
	}
	
	/*
	 * 导购员绑定
	 */
	public function binding() {
		if(IS_POST) {
			$guide_num = I('post.guide_num');
			//查询该导购员所处的信息
			$map['guide_num'] = $guide_num ;
			$map['type'] = 3;
			$res = M('Guide')->where($map)->find();
			if($this->openid == $res['openid']) {
				$this->error("不能绑定自己!");
			}
			//查询该门店编号对应的id
			$store_id = M('Stores')->where(array('number' => $res['store_num']))->find();
			if(!$res) {
				$this->error("该导购员不存在!");
			}
			//查看该会员的绑定信息
			$info = M('Member')->where(array('openid' => $this->openid))->find();
			if(!empty($info['guide_num'])) {   //已经绑定了导购员
				$this->error("你已绑定了导购员!");
			}
			if((empty($info['sid'])) && empty($info['guide_num'])) {   //没有绑定任何角色
				$info['sid'] = $store_id['id'] ;
				$info['guide_num'] = $guide_num ;
				$info['type'] = 2;
				M('Member')->where(array('openid' => $this->openid))->save($info);
				
				$res1 = M('Guide_binding')->where(array('member_id' => $info['id']))->find();
				if(!$res1) {
					$myinfo['guide_num'] = $guide_num ;
					$myinfo['member_id'] = $info['id'] ;
					$myinfo['openid'] = $res['openid'] ;
					$myinfo['member_name'] = $info['nickname'] ;
					$myinfo['status'] = 1 ;
					$myinfo['create_date'] = date("Y-m-d H:i:s");
					M('Guide_binding')->add($myinfo) ? $this->success('绑定成功!',U('Shop/index/index', '', true, true)) : $this->error('绑定失败!');
					//M("Guide_binding")->data($myinfo)->add() ? $this->success("绑定成功!" , U('Shop/index/index', '', true, true)) : $this->error("绑定失败!");
				}
			} 
			if((!empty($info['sid'])) && empty($info['guide_num'])) {
				//查询该会员的门店编号
				$store_num = M('Stores')->field('number')->where(array('id' => $info['sid']))->find();
				if($res['store_num'] === $store_num['number']) {
					$info['guide_num'] = $guide_num ;
					$info['type'] = 2;
					M('Member')->where(array('openid' => $this->openid))->save($info);
					
					$res = M('Guide_binding')->where(array('member_id' => $info['id']))->find();
					if(!$res) {
						$myinfo['guide_num'] = $guide_num ;
						$myinfo['member_id'] = $info['id'] ;
						$myinfo['openid'] = $res['openid'] ;
						$myinfo['member_name'] = $info['nickname'] ;
						$myinfo['status'] = 1 ;
						$myinfo['create_date'] = date("Y-m-d H:i:s");
						$res1 = M("Guide_binding")->data($myinfo)->add() ? $this->success("绑定成功!" , U('Shop/index/index', '', true, true)) : $this->error("绑定失败!");
					}
				} else{
					$this->error("请输入该门店的导购员编号");
				}
			} 
		} else {
			$this->display();
		}
		
	}
	
	/**
	 * 佣金提现
	 */
	public function tixian() {
		$info = $this->getMember($this->oauth(), null, true);
		
		//获取该导购员的可提现金额
		$guide = M('Guide')->where(array('openid' => $this->openid))->find();
		$data = M('Guide')
					->field('tixianprice')
					->where(array('guide_num' => $guide['guide_num']))
					->select();
		foreach ($data as &$val) {
			if(empty($val['tixianprice'])) {
				$val['tixianprice'] = 0 ;
			}
		}
		$cofig = M("Store")->field('number,min_money')->find(); //读取商城的提现的配置
		if(!$guide) {
			$this->error("你还不是导购员，请先申请!");
		}
		if(IS_POST) {
			$price = I('post.price');
			//当月提现次数
			$num = M("Tixian_record")
					->where( array("create_date" => array('egt', date('Y-m-01', time())),"guide_num" => $guide['guide_num']) )
					->group('guide_num')
					->select(); 
			if($price > $data['0']['tixianprice']) {
				$ajaxdata = array();
                $ajaxdata['status'] = 0;
                $ajaxdata['info'] = '提现的金额高于可提现佣金';
                $this->ajaxReturn($ajaxdata);
			}
			
			if (count($num) >= $cofig['number']) {/* 如果超过限制弹出提示的信息 */
                $ajaxdata = array();
                $ajaxdata['status'] = 0;
                $ajaxdata['info'] = '当月提现的次数超过限制';
                $this->ajaxReturn($ajaxdata);
            }
			if($price >= $cofig['min_money']) {   //如果申请提现的金额超过设定的最低金额，则继续操作
				$mydata['price'] = $price ;
				$mydata['openid'] = $this->openid ;
				$mydata['guide_num'] = $guide['guide_num'] ;
				$mydata['create_date'] = date("Y-m-d H:i:s");
				$mydata['status'] = 3 ;
				$result = M('Tixian_record')->add($mydata) ;
				if($result) {
					//在导购员表减去响应的金额
					$guide = M('Guide')->where(array('openid' => $this->openid))->find();
					$myinfo['tixianprice'] = $guide['tixianprice'] - $price ;
					$myinfo['create_date'] = date("Y-m-d H:i:s");
					M('Guide')->where(array('openid' => $this->openid))->save($myinfo);
				}
				
                $ajaxdata = array(); //操作成功返回提示
                $ajaxdata['status'] = 1;
                $ajaxdata['info'] = '申请成功，请耐心等待后台审核';
                $this->ajaxReturn($ajaxdata);
			} else {
				$ajaxdata = array();
                $ajaxdata['status'] = 0;
                $ajaxdata['info'] = '提现的金额低于最低额度';
                $this->ajaxReturn($ajaxdata);
			}
			
		} else {
			$this->assign("data",$data['0']);
			$this->assign("ptitle","佣金提现");
			$this->assign("info",$info);
			$this->display();
		}
		
	}

}
