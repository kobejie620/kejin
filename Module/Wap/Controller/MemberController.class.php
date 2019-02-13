<?php
 
namespace Wap\Controller;

class MemberController extends WapController {

	/**
     * 用户信息检测
     */
    public function _initialize() {
        parent::_initialize();
       $member = $this->getMember($this->oauth(), null, false);
	   //判断该会员是否导购员
	   $map['openid'] = $member['openid'] ;
	   $map['type'] = 3 ;
	   $result = M('Guide')->where($map)->find();
	   if($result) {
		   redirect(U('Wap/Guide/info'));
	   }
    }
	
    /**
     * 显示会员信息
     */
    public function index() {
        $this->getMember($this->oauth(), null, true);
        $null_href = 'javascript:void(0)';
        $total = array();
        $pay_map = array('openid' => $this->openid, 'status' => array('in', '1,2'), 'pay_status' => '0');
        $total['pay'] =M('StoreOrder')->where($pay_map)->count();
        if ($total['pay'] > 0) {
            $total['pay_url'] = U('Shop/MyOrder/index', array('_getcode_' => encode(serialize($pay_map))));
        } else {
            $total['pay_url'] = $null_href;
        }

        $route_map = array('openid' => $this->openid, 'pay_status' => '1', 'status' => array('in', '1,2'), 'send_status' => '0');
        $total['route'] = (int) M('StoreOrder')->where($route_map)->count();
        if ($total['route'] > 0) {
            $total['route_url'] = U('Shop/MyOrder/index', array('_getcode_' => encode(serialize($route_map))));
        } else {
            $total['route_url'] = $null_href;
        }

        $routed_map = array('openid' => $this->openid, 'status' => array('in', '1,2'), 'send_status' => '1');
        $total['routed'] = (int) M('StoreOrder')->where($routed_map)->count();
        if ($total['routed']) {
            $total['routed_url'] = U('Shop/MyOrder/index', array('_getcode_' => encode(serialize($routed_map))));
        } else {
            $total['routed_url'] = $null_href;
        }

        $success_map = array('openid' => $this->openid, 'send_status' => '2');
        $total['success'] = (int) M('StoreOrder')->where($success_map)->count();

        if ($total['success'] > 0) {
            $total['success_url'] = U('Shop/MyOrder/index', array('_getcode_' => encode(serialize($success_map))));
        } else {
            $total['success_url'] = $null_href;
        }

        $this->assign('total', $total);
        $this->assign('ptitle', '会员中心');
        $this->display();
    }

    /**
     * 会员信息编辑
     */
    public function info($_msg_ = null, $member = null) {
        if (IS_POST) {
            $id = I('post.id');
            empty($id) && $this->error('网络繁忙，请稍后再试');
   
            unset($_POST['id']);
            $res = M('Member')->data($_POST)->where(array('id' => $id))->save();
   
            if (!empty($res)) {
                $this->success('保存成功', U('Wap/Member/index'));

            } else {
                $this->error('保存失败，请稍后再试');
            }
        } else {
            if (empty($member)) {
                $this->getMember($this->oauth());
            } else {
                $this->assign('member', $member);
            }
            empty($_msg_) || $this->assign('msg', $_msg_);
            $this->assign('ptitle', '会员资料');
            $this->display(T('Wap@Member:info'));
        }
        die();
    }

    /**
     * 会员积分记录
     */
    public function integral() {
        $this->getMember($this->oauth(), null, true);
        val('_sort', 'desc');
        val('_order', 'id');
        $this->ptitle = '积分历史';
        parent::index(D('MemberIntegral'));
    }

    /**
     * 积分记录过滤
     * @param type $model
     * @param array $map
     */
    protected function _integral_filter($model, &$map) {
        $map['openid'] = $this->openid;
    }

    /**
     * 会员地址管理
     */
    public function address() {
        $this->_bind_model = 'MemberAddress';
        if (IS_POST) {
            $data = I('post.', array());
            $data['openid'] = $this->openid;
            $result = $this->_save($data, M('MemberAddress'));
            if ($result['status'] && $result['data']['id'] && intval($result['data']['is_default']) === 2) {
                $this->setDefaultAddress($result['data']['id']);
            } else {
                $this->ajaxReturn($result);
            }
        } else {
            $this->getMember($this->oauth(), null, true);
            $this->_page_on = false;
            $this->ptitle = '收货地址管理';
            val('_order', 'is_default');
            val('_sort', 1);
            parent::index(null, array('openid' => $this->openid), 'address');
        }
    }

    /**
     * 删除地址
     */
    public function delAddress() {
        $this->_bind_model = 'MemberAddress';
        $this->del();
    }

    /**
     * 设置默认收货地址
     * @param type $addressid
     */
    public function setDefaultAddress($addressid = null) {
        $openid = $this->oauth();
        if ($openid) {
            $data = array();
            $data['id'] = is_null($addressid) ? I('get.id', '0', 'intval') : $addressid;
            $data['openid'] = $openid;
            $data['is_default'] = '2';
            $r1 = M('MemberAddress')->where(array('openid' => $openid))->setField('is_default', '1');
            $r2 = M('MemberAddress')->save($data);
            if ($r1 !== false && $r2 !== false) {
                $this->success('设置默认收货地址成功！');
                exit;
            }
        }
        $this->error('设置默认收货地址失败，请稍候再试！');
    }
    
    /**
     * 线下门店
     * @author chenrongbin
     */
    public function extension() {
        if(IS_POST) {
            $code = I('post.code');
            $store_id = I('post.store_id');
            $info = M('PromotionInfo')->where(array('cdkey' => $code,'status'=>1))->find();
            $number = M('Stores')->where(array('number' => $store_id))->find(); 
            
            //判断该推广员是否已经兑换过
            $openid = $this->openid;
            //$num = M('ExchangeRecord')->where(array('openid' => $openid))->find();
            if(($info != null) && ($number != null)) {
					 $url = U('Wap/Member/exchange', array('code' => $info['cdkey'],'store_id' => $store_id));
					 M("PromotionInfo")->where(array("cdkey" => $code, "status" => 1))->find() ? $this->success("正在处理！",$url) : $this->error("您输入的兑换码已被使用!");  
				
                        
            } else {
                $this->error("兑换码或门店编号错误!");
            }
        }else {
            $this->display();    
        }
    }
    
    /**
     * 确认兑换
     * @author chenrongbin
     */
    public function exchange() {
        $user = M('Member')->where(array('openid' => $this->openid))->find();    
        if(IS_POST) {
            $code = I('post.code');
            $store_id = I('post.store_id');
            $latitude = I('post.latitude');
            $longitude = I('post.longitude');
            $store_info = M('Stores')->where(array('number' => $store_id))->find();
            //获取奖品信息
            $data = M('PrizePeizhi')->find();
//            $filename = APP_PATH . 'Member/Conf/exchange_config.php';
//            $config = file_get_contents($filename);
//            $data = json_decode($config, true);
            
            //获取兑换码信息
            M("PromotionInfo")->where(array("cdkey" => $code))->find();
            $info = array();   
            $info['code'] = $code;
            $info['store_id'] = $store_info['id'];
            $info['username'] = $user['name'];
            $info['openid'] = $this->openid;
            $info['store_name'] = $store_info['name'];
            $info['prize_name'] = $data['prize_name'] ;
            $info['prize_img'] = $data['prize_img'] ;
            $info['create_date'] = date("Y-m-d H-i:s");
            $info['result'] = 1;

            $str = array();
            $str['status'] = 2 ;
            $str['stores'] = $store_id ;
            $str['use_time'] = date("Y-m-d H-i:s");
            
            $url = U('Wap/Store/index',array('latitude'=> $latitude ,'longitude'=> $longitude));
            M('PromotionInfo')->where(array('cdkey' => $code))->save($str) && M("ExchangeRecord")->data($info)->add() ? $this->success("兑换成功！" ,$url) : $this->error("兑换失败！");

       
        } else {
//            $config = M()->where(array('status'=>2))->find();
//            $wechat = new \Library\Util\Api\Wechat($config);
//            $this->assign('jsSign', $wechat->getJsSign(__SELF__));
            $code = I('get.code');
            $store_id = I('get.store_id');
            $info = M('PrizePeizhi')->find();
            $this->assign("code",$code);
            $this->assign("info",$info);
            $this->assign("store_id",$store_id);
            $this->display();
        }
    }
	
	/*
	 * 我的成绩
	 */
	public function score() {
		$info = $this->getMember($this->oauth(), null, true);
		$member = M('Member')->where(array('openid' => $this->openid))->find();
		$data = M('Member_score')->where(array('openid' => $this->openid))->find();
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
				->where(array('member_id' => $member['id'] ,'status' => 2))
				->find();
		$total_ticheng = $member['tixianprice'] + $ticheng['price'];
		
		//用户购买次数
		$goumai = M('Member_discount')->where(array('memberid' => $member['id']))->count();
		//推荐关注
		$guanzhu = M('Promotion_user')->where(array('promotion_openid' => $this->openid))->count();
		$this->assign("guanzhu",$guanzhu);
		$this->assign("goumai",$goumai);
		$this->assign("info",$info);
		$this->assign("data",$data);
		$this->assign("total",$total_ticheng);
		$this->assign("ptitle","我的成绩");
        $this->display();
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
		$map['d.create_time'] = array("like",$date . "%");
        $map['d.memberid'] = $member['id'];
		$this->assign("date",$date);
    }
	
	public function _rebate_list_filter(&$model, &$map) {
//		$model->field('d.*,sum(d.price) price , sop.product_name , so.accept_name')
//			  ->join('left join __STORE_ORDER__ so on d.order_id = so.id')
//			  ->join('left join __STORE_ORDER_PRODUCT__ sop on d.order_id = sop.order_id')
//			  ->group('DATE_FORMAT(d.create_time,\'%Y-%m-%d\')')
//			  ->alias('d');  
		
		$model->field('d.* , sop.product_name , so.accept_name')
			  ->join('left join __STORE_ORDER__ so on d.order_id = so.id')
			  ->join('left join __STORE_ORDER_PRODUCT__ sop on d.order_id = sop.order_id')
			  ->alias('d');  
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
	 * 佣金提现
	 */
	public function tixian() {
		$info = $this->getMember($this->oauth(), null, true);
		
		//获取该会员的可提现金额
		$data = M('Member')->where(array('openid' => $this->openid))->select();
		foreach ($data as &$val) {
			if(empty($val['tixianprice'])) {
				$val['tixianprice'] = 0 ;
			}
		}
		$cofig = M("Store")->field('number,min_money')->find(); //读取商城的提现的配置
		if(IS_POST) {
			$price = I('post.price');
			//当月提现次数
			$num = M("Tixian_record")
					->where( array("create_date" => array('egt', date('Y-m-01', time())),"guide_num" => $guide['guide_num']) )
					->group('member_id')
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
				$mydata['member_id'] = $data['0']['id'] ;
				$mydata['create_date'] = date("Y-m-d H:i:s");
				$mydata['status'] = 3 ;
				$result = M('Tixian_record')->add($mydata) ;
				if($result) {
					//在member表减去响应的金额
					$member = M('Member')->where(array('openid' => $this->openid))->find();
					$myinfo['tixianprice'] = $member['tixianprice'] - $price ;
					$myinfo['create_date'] = date("Y-m-d H:i:s");
					M('Member')->where(array('openid' => $this->openid))->save($myinfo);
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
		$member = M('Member')->where(array('openid' => $this->openid))->find();
		$map['tr.create_date'] = array("like",$date . "%");
        $map['tr.member_id'] = $member['id'];
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

}
