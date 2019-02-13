<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wap\Controller;

class PromotionController extends WapController {
/*
 power by zenglingwen
 * 用户推广申请处理 
 * wx_promotion_user表  是用户推广人信息表
 * wx_promotion_info表  是用户的推广链接，cdkey,已经使用状态
 * 
 */
/**
     * 会员模块初始化方法
     */
    public function _initialize() {
        $this->openid = session('openid');
//        $this->openid  = 'oAeaZjkJR9yIIawfwsyES-u4G0rM';
        if (empty($this->openid)) {
            $this->call(); 
        }
    }

    public function promotion() {
		if (empty($this->openid)) {
			$this->success('没有获取到微信id，请在微信端打开', U('Shop/index/index', '', true, true));
		} else {
			$user = M('Member')->where(array('openid' => $this->openid))->find();
			//判断该会员是否导购员
			$mymap['openid'] = $this->openid ;
			$mymap['type'] = 3 ;
			$res = M('Guide')->where($mymap)->find();
			if($res) {
				$this->assign("guide_num",$res['guide_num']);
			}
		}
		$status = 0;
		if ($user['address'] == '' || $user['name'] == '' || $user['phone'] == '') {
			$this->assign('name', $user['name']);
			$this->assign('phone', $user['phone']);
			$this->assign('address', $user['address']);
			$this->assign('province', $user['province']);
			$this->assign('city', $user['city']);
			$this->assign('street', $user['street']);
			$status = 1;
		} else {
			/* 开始处理用户的推广信息 */

			$userid = M('PromotionUser')
					->where(array('a.promotion_openid' => $this->openid, 'a.status' => '1'))
					->field('wm.headimgurl,wm.nickname,a.create_time')
					->join('LEFT JOIN wx_wechat_member wm ON wm.openid = a.uid_openid')
					->alias('a')
					->select();
			$num = count($userid); //已经关注的用户数
			$this->assign('user', $userid);
			$this->assign('num', $num);
			/* 推广活动配置项 */
			$info = M('PrizePeizhi')->find();
			$this->assign('account', $info['account']);
			$this->assign('prize_name', $info['prize_name']);
			$this->assign('prize_img', $info['prize_img']);
			$this->assign('cftitle', $info['cftitle']);
			$this->assign('ftitle', $info['ftitle']);
			$this->assign('fdesc', $info['fdesc']);
			$userinfo = M('PromotionInfo')->where(array('openid' => $this->openid))->select();
			if ($num < $info['account']) {//用户数没到30，显示还差多少用户的页面
				$status = 2;
			} else {//如果关注的用户数超过30，生成唯一兑换码并显示不同页面
				$status = 3;
				if (floor($num / $info['account']) > count($userinfo)) {

					$id = M('Member')->field('id')->where(array('openid' => $this->openid))->find();
					$inum = floor($num / $info['account']) - count($userinfo);
					for ($i = 0; $i < $inum; $i++) {
						$data['openid'] = $this->openid;
						$data['cdkey'] = uniqid();
						$data['uid'] = $id['id'];
						$data['status'] = 1;
						$data['create_time'] = date('Y-m-d H:i:s', time());
						M('PromotionInfo')->add($data);
					}
					//查找所有的key,如果存在cdkey 
					$cdkey = M('PromotionInfo')->where(array('openid' => $this->openid))->select();
					$this->assign('cdkey', $cdkey);
				} else {
					//查找所有的key,如果存在cdkey
					$cdkey = M('PromotionInfo')->where(array('openid' => $this->openid))->select();
					$this->assign('cdkey', $cdkey);
				}
			}
		}
		switch ($status) {
			case 1:
				$title = '完善信息';
				break;

			default:
				$title = '推广信息';
				break;
		}
		$this->assign('title', $title);
		$this->assign('host', $_SERVER['HTTP_HOST']);
		$this->assign('id', $user['id']); //用户的id 
		$this->assign('status', $status);
		$this->display();
	}

	/*
  * 处理用户点击推广链接事件
  */   
	public function clickpromotion() {
	   if ($this->openid) {//如果存在用户登录继续下一步操作
		   //用户点击获得积分
		   $jifen = M('Jifen_set')->where(array('type' => "点击积分"))->find();
		   
		   //判断该用户今天是否已获得点击积分
			$map['openid'] = $this->openid ;
			$date = date("Y-m-d");
			$map['create_date'] = array("like",$date . "%");
			$map['desc'] = "点击积分";
			$myresult = M('Member_integral')->where($map)->find();
			if(empty($myresult)) {
				$info['openid'] = $this->openid ;
				$info['desc'] = $jifen['type'] ;
				$info['integral'] = $jifen['integral_number'] ;
				$info['create_date'] = date("Y-m-d H:i:s");
				M("Member_integral")->data($info)->add();

				//把积分加到member表
				$res = M('Member')->where(array('openid' => $this->openid))->find();
				$myinfo['total_integral'] = $res['total_integral'] + $jifen['integral_number'] ;
				$myinfo['integral'] = $myinfo['total_integral'] - $res['used_integral'] ;
				M('Member')->where(array('openid' => $this->openid))->save($myinfo);
			}

		   $guide_num = I('get.guide_num') ;
		   if(!empty($guide_num)) {
			   /* 初始化用户点击人数 */
			   $mydata['create_date'] = date('Y-m-d H:i:s'); /* 当天日期 */
			   $result = M('Guide_score')->where(array( 'guide_num' => $guide_num))->find();
			   if ($result) {
				   $mydata['dianji'] = $result['dianji'] + 1 ; 
				   M('Guide_score')->where(array('guide_num' => $guide_num))->save($mydata);
			   } else {
				   $mydata['openid'] = $this->openid ;
				   $mydata['guide_num'] = $guide_num ;
				   $mydata['dianji'] = 1 ;
				   M("Guide_score")->data($mydata)->add();
			   }
			   
				$res2 = M('Guide')->where(array('guide_num' => $guide_num))->find();
				if($this->openid !== $res2['openid']) {    //判断是否自己点击
					//判断该点击用户是否新用户
					$res1 = M('Member')->where(array('openid' => $this->openid))->find();
					if((empty($res1['guide_num'])) && (empty($res1['sid']) && (empty($res1['member_id'])))) {
						$info['guide_num'] = $guide_num ;
						$info['type'] = 1 ;
						$myresult = M('Member')->where(array('openid' => $this->openid))->save($info);
						//查看该会员的绑定信息
						$myres = M('Member')->where(array('openid' => $this->openid))->find();
						$res3 = M('Guide_binding')->where(array('member_id' => $res['id']))->find();
						if($myresult && !$res3) {
							$myinfo1['guide_num'] = $guide_num ;
							$myinfo1['member_id'] = $myres['id'] ;
							$myinfo1['openid'] = $res2['openid'] ;
							$myinfo1['member_name'] = $myres['nickname'] ;
							$myinfo1['status'] = 1 ;
							$myinfo1['create_date'] = date("Y-m-d H:i:s");
							M("Guide_binding")->data($myinfo1)->add();
						}
					}
				}
		   } 
		   //绑定会员
		    $member_id = I('get.tuijian');
		   if(!empty($member_id)) {
			   /* 初始化用户点击人数 */
			   $mydata['create_date'] = date('Y-m-d H:i:s'); /* 当天日期 */
			   $result = M('Member_score')->where(array( 'member_id' => $member_id))->find();
			   if ($result) {
				   $mydata['dianji'] = $result['dianji'] + 1 ; 
				   M('Member_score')->where(array('member_id' => $member_id))->save($mydata);
			   } else {
				   $mydata['openid'] = $this->openid ;
				   $mydata['member_id'] = $member_id ;
				   $mydata['dianji'] = 1 ;
				   M("Member_score")->data($mydata)->add();
			   }
			   
			   $res2 = M('Member')->where(array('id' => $member_id))->find();
				if($this->openid !== $res2['openid']) {
					//判断该点击用户是否新用户
					$res1 = M('Member')->where(array('openid' => $this->openid))->find();
					if((empty($res1['guide_num'])) && (empty($res1['sid']) && (empty($res1['member_id']))))  {
						$info['member_id'] = $member_id ;
						$info['type'] = 3 ;
						M('Member')->where(array('openid' => $this->openid))->save($info);
					}
				}
		   }
		   
		   $uid = M('Member')->where(array('openid' => $this->openid))->find();
		   $data['uid_openid'] = $this->openid;
		   $data['status'] = 1; //推荐状态
		   $status = 0;
		   $result = M('PromotionUser')->where($data)->find();
		   if ($result) {
			   $this->success('你已经接受过其他用户的推荐',U('Shop/index/index','', true, true));
			   $status = 1;
		   } elseif ($uid) {
			   $this->success('你已经是注册会员，不可以再次接受别人的推荐',U('Shop/index/index','', true, true));
			   $status = 2;
		   }
	   } else {
		   $this->success('请在微信端打开');   //如果用户没登录提醒 
	   }
	   $this->assign("guide_num",$guide_num);
	   $this->assign('tuijian', I('get.tuijian'));
	   $this->assign('status', $status);
	   $this->display();
   }

    /*
	 * 处理用户补全资料申请推广的处理
	 */

	public function datum() {
        $data = array();
        $where['name'] = I('post.name');
        $where['phone'] = I('post.phone');
        $where['province'] = I('post.province');
        $where['city'] = I('post.city');
        $where['street'] = I('post.district');
        $where['address'] = I('post.address');
        $uid = M('Member')->where(array('openid' => $this->openid))->find();
        if(empty($uid)){ 
			$where['openid'] =  $this->openid;
			$result = M('Member')->add($where);
        }else{
       $result = M('Member')->where(array('openid' => $this->openid))->save($where); //如果会员表存在的记录，执行更新操作
        }
        if ($result) {//处理成功 
            $data['status'] = 1;
            $data['info'] = '更新成功';
            $data['url'] = U('Shop/Promotion/promotion');
            $this->ajaxReturn($data);
        } else {

            $data['status'] = 0;
            $data['info'] = '更新失败';
            //$data['url'] = U('WAP/Promotion/promotion');
            $this->ajaxReturn($data);
        }
    }
/*处理接受推荐用户的资料补全*/
    
    public function click() {
       if($this->openid){ 
        $url = I('post.tuijian');
        $where['name'] = I('post.name');
        $where['phone'] = I('post.phone');
        $where['province'] = I('post.province');
        $where['city'] = I('post.city');
        $where['street'] = I('post.district');
        $where['address'] = I('post.address'); 
        $where['openid'] =  $this->openid;
		
        $uid = M('Member')->where(array('openid' => $this->openid))->find();
        $tj_sid = M('Member')->where(array('id' =>$url))->find();
		if(!empty($tj_sid['sid'])){
			$where['sid'] = $tj_sid['sid']; 
		}else{
			$where['sid'] = 190;   
		}
        if(empty($uid)){
			$result = M('Member')->add($where); 
        }else{
			$result = M('Member')->where(array('openid' => $this->openid))->save($where); //如果会员表存在的记录，执行更新操作   
        }
//没有接受过推荐的用户存入记录
        if($result){
            $data = array();
            $data['promotion_id'] = $url; //推荐人的uid
            $promotion_openid = M('Member')->where(array('id' => $url))->field('openid')->find(); //通过推荐人的uid获取推荐人的openId
            if(empty($promotion_openid['openid'])){
             $this->success('无效的推荐用户',U('Shop/index/index','', true, true));    
            }
            $data['promotion_openid'] = $promotion_openid['openid'];
            $data['uid_openid'] = $this->openid; //被推荐人的openid
            $uid = M('Member')->where(array('openid' => $this->openid))->field('id')->find(); //通过被推荐人的openid获取被推荐人的UId
            $data['uid'] = $uid['id'];
            if($data['uid']){
				$data['status'] = 1; //推荐状态 
            }else{
              $data['status'] = 2;   
            }
            $data['create_time'] = date('Y-m-d H:i:s', time());
            $info = M('PromotionUser')->data($data)->add();//添加推荐记录
            if($info){
				$da['status'] = 1;
				$da['info'] = '更新成功';
				$da['url'] = U('Shop/index/index','', true, true);
				$this->ajaxReturn($da);

            }else{
            $da['status'] = 0;
            $da['info'] = '更新失败';
            $da['url'] =U('Shop/index/index', '', true, true);
            $this->ajaxReturn($da);  
            }
       }}  else {
             $da['status'] = 0;
            $da['info'] = '更新失败，获取不到你的信息';
            $da['url'] =U('Shop/index/index', '', true, true);
            $this->ajaxReturn($da);       
    
}
        
    }

    public function call() {
        if (!empty($this->openid)) {
            return $this->openid;
        }
        $wechat = $this->getInstanceWechat();
        if (I('get.code', false, 'trim')) {
            $result = $wechat->getOauthAccessToken();
            if ($result !== false) {
                session('openid', $result['openid']);
//                $result['expires_in'] = $result['expires_in'] + time();

                $result = $wechat->getOauthUserinfo($result['access_token'], $result['openid']);
                $a = M('WechatMember')->where(array('openid' => $result['openid']))->field('id')->find();
                $result['update_date'] = date('Y-m-d H:i:s', time());
                if ($a == 0) {
                    M('WechatMember')->add($result);
                } else {
                    M('WechatMember')->where(array('openid' => $result['openid']))->save($result);
                }
                redirect(session('oauth_referer'));
            } else {
                R('Wap/Empty/error', array('微信网页授权失败', $wechat->errMsg));
            }
        } else {
            session('oauth_referer', get_domain() . url_filter());
            redirect($wechat->getOauthRedirect(U('Wap/Promotion/call', '', true, true), '', 'snsapi_userinfo'));
        }
    }
	
	//分享成功后存入记录
	public function share() {
		if($this->openid){ 
			$guide_num = I('post.guide_num');
			$member_id = I('post.tuijian');
			//分享获得积分
			$jifen = M('Jifen_set')->where(array('type' => "分享积分"))->find();
			//判断该用户今天是否已获得分享积分
			$map['openid'] = $this->openid;
			$date = date("Y-m-d");
			$map['create_date'] = array("like", $date . "%");
			$map['desc'] = "分享积分";
			$myresult = M('Member_integral')->where($map)->find();
			if (empty($myresult)) {
				$info['openid'] = $this->openid;
				$info['desc'] = $jifen['type'];
				$info['integral'] = $jifen['integral_number'];
				$info['create_date'] = date("Y-m-d H:i:s");
				M("Member_integral")->data($info)->add();

				//把积分加到member表
				$res = M('Member')->where(array('openid' => $this->openid))->find();
				$mydata['total_integral'] = $res['total_integral'] + $jifen['integral_number'];
				$mydata['integral'] = $mydata['total_integral'] - $res['used_integral'];
				M('Member')->where(array('openid' => $this->openid))->save($mydata);
			}

			//导购员分享
			if(!empty($guide_num)) {
				/* 初始化记录访问人数 */
				$data['create_date'] = date('Y-m-d H:i:s'); /* 当天日期 */
				$result = M('Guide_score')->where(array( 'guide_num' => $guide_num))->find();
				if ($result) {
					$data['fenxiang'] = $result['fenxiang'] + 1 ; 
					M('Guide_score')->where(array('guide_num' => $guide_num))->save($data);
				} else {
					$data['openid'] = $this->openid ;
					$data['guide_num'] = $guide_num ;
					$data['fenxiang'] = 1 ;
					M("Guide_score")->data($data)->add();
				}
			}
			//会员分享
			if(empty($guide_num)) {
				/* 初始化记录访问人数 */
				$data['create_date'] = date('Y-m-d H:i:s'); /* 当天日期 */
				$result = M('Member_score')->where(array( 'member_id' => $member_id))->find();
				if ($result) {
					$data['fenxiang'] = $result['fenxiang'] + 1 ; 
					M('Member_score')->where(array('member_id' => $member_id))->save($data);
				} else {
					$data['openid'] = $this->openid ;
					$data['member_id'] = $member_id ;
					$data['fenxiang'] = 1 ;
					M("Member_score")->data($data)->add();
				}
			}
		} else {
            $this->success('请在微信端打开');   //如果用户没登录提醒 
        }
	}

}


?>