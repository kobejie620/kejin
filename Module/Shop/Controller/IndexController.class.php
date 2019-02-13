<?php

namespace Shop\Controller;

/**
 * WAP商城的商品列表页
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/22 10:54:32
 */
class IndexController extends ShopController {

	/**
	 * 绑定对应的模型名
	 * @var type 
	 */
	protected $_bind_model = 'StoreProduct';

	/**
	 * 列表过滤处理
	 * @param type $model
	 * @param type $map
	 */
	protected function _index_filter($model, &$map) {
		$this->keys = I("get._keys", '');
		$map["name"] = array("like", "%" . $this->keys . "%");
		$map['xianshi'] = array('neq', 1);
		$map['miaosha'] = array('neq', 1);

		/* 条件 */
		$map['status'] = '2';
		/* 获取商城信息 */
		$cat = I('get.cat'); //获取链接的分类
		$cats = explode(',', $cat);
		if (I('get.cat')) {
			if (count($cats) > 1) {
				$map['cat_id'] = array('in', $cats);
			} else {
				$map['cat_id'] = array('eq', $cat);
			}
		}

		$info = M("Store")->find(1);
		$info['link'] = explode('|', "{$info['link']}");
		if (!empty($info['url'])) {
			redirect($info['url']);
		}
		$img = json_decode($info['content']);
		$this->assign('llimg', $img);
		/* 分类菜单 */
		$pid = M("StoreProductCat")->where(array('pid' => 0))->select();
		foreach ($pid as $key => $value) {
			$pid[$key]['voo'] = M("StoreProductCat")->where(array('pid' => $value['id']))->select();
		}
		
		$this->assign('pid', $pid);
		$this->assign('ptitle', $info['name']);

		$this->assign('store', $info);
	}

	/**
	 * 处理产品模型产品
	 * 
	 * @param array $vo
	 */
	protected function _form_filter(&$model, &$vo) {
		//判读是否点击分享链接进来的
		$guide_num = I('get.guide_num');
		$member_id = I('get.member_id');
		if((!empty($guide_num)) || (!empty($member_id))) {
			//用户点击获得积分
			$jifen = M('Jifen_set')->where(array('type' => "点击积分"))->find();
			//判断该用户今天是否已获得点击积分
			$map['openid'] = $this->openid;
			$date = date("Y-m-d");
			$map['create_date'] = array("like", $date . "%");
			$map['desc'] = "点击积分";
			$myresult = M('Member_integral')->where($map)->find();
			if (empty($myresult)) {
				$myinfo['openid'] = $this->openid;
				$myinfo['desc'] = $jifen['type'];
				$myinfo['integral'] = $jifen['integral_number'];
				$myinfo['create_date'] = date("Y-m-d H:i:s");
				M("Member_integral")->data($myinfo)->add();

				//把积分加到member表
				$res = M('Member')->where(array('openid' => $this->openid))->find();
				$myinfo1['total_integral'] = $res['total_integral'] + $jifen['integral_number'];
				$myinfo1['integral'] = $myinfo1['total_integral'] - $res['used_integral'];
				M('Member')->where(array('openid' => $this->openid))->save($myinfo1);
			}
		}

		//绑定导购员
		if(!empty($guide_num)) {
			/* 初始化用户点击人数 */
			$mydata['create_date'] = date('Y-m-d H:i:s'); /* 当天日期 */
			$result = M('Guide_score')->where(array('guide_num' => $guide_num))->find();
			if ($result) {
				$mydata['dianji'] = $result['dianji'] + 1;
				M('Guide_score')->where(array('guide_num' => $guide_num))->save($mydata);
			} else {
				$mydata['openid'] = $this->openid;
				$mydata['guide_num'] = $guide_num;
				$mydata['dianji'] = 1;
				M("Guide_score")->data($mydata)->add();
			}
			
			//判断是否是自己点击
			$res2 = M('Guide')->where(array('guide_num' => $guide_num))->find();
			if($this->openid !== $res2['openid']) {
				//判断该点击用户是否新用户
				$res1 = M('Member')->where(array('openid' => $this->openid))->find();
				if((empty($res1['guide_num'])) && (empty($res1['sid']))) {
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
		if(!empty($member_id)) {
			$this->assign("memberid",$member_id);
			/* 初始化用户点击人数 */
			$mydata['create_date'] = date('Y-m-d H:i:s'); /* 当天日期 */
			$result = M('Member_score')->where(array('member_id' => $member_id))->find();
			if ($result) {
				$mydata['dianji'] = $result['dianji'] + 1;
				M('Member_score')->where(array('member_id' => $member_id))->save($mydata);
			} else {
				$mydata['openid'] = $this->openid;
				$mydata['member_id'] = $member_id;
				$mydata['dianji'] = 1;
				M("Member_score")->data($mydata)->add();
			}

			$res2 = M('Member')->where(array('id' => $member_id))->find();
			if ($this->openid !== $res2['openid']) {
				//判断该点击用户是否新用户
				$res1 = M('Member')->where(array('openid' => $this->openid))->find();
				if ((empty($res1['guide_num'])) && (empty($res1['sid']) && (empty($res1['member_id'])))) {
					$info['member_id'] = $member_id;
					$info['type'] = 3;
					M('Member')->where(array('openid' => $this->openid))->save($info);
				}
			}
		}

		//判断该会员是否导购员
		$mymap['openid'] = $this->openid ;
		$mymap['type'] = 3 ;
		$res = M('Guide')->where($mymap)->find();
		if($res) {
			$this->assign("guide_num",$res['guide_num']);
		}
		//获取该会员的信息
		$member = M('Member')->where(array('openid' => $this->openid))->find();
		$this->assign("member_id",$member['id']);
		//处理图片信息
		$vo['must_know'] = str_replace("\n", '<br>', $vo['must_know']);
		$vo['img'] = explode('|', "{$vo['img']}");
		$productModel = D('Store/StoreProductModel');
		$vo['model_params'] = $productModel->parseParams($vo['model_params']);
		$this->ptitle = $vo['name'];
		$this->assign("openid",$this->openid);
	}
	
	//分享成功后存入记录
	public function share() {
		if($this->openid){ 
			$guide_num = I('post.guide_num');
			$member_id = I('post.member_id');
			//分享获得积分
			$jifen = M('Jifen_set')->where(array('type' => "分享积分"))->find();
			//判断该用户今天是否已获得分享积分
			$map['openid'] = $this->openid;
			$date = date("Y-m-d");
			$map['desc'] = "分享积分";
			$map['create_date'] = array("like", $date . "%");
			$myresult = M('Member_integral')->where($map)->find();
			if (empty($myresult)) {
				$info['openid'] = $this->openid;
				$info['desc'] = $jifen['type'];
//				$info['guide_num'] = $guide_num;
				$info['integral'] = $jifen['integral_number'];
				$info['create_date'] = date("Y-m-d H:i:s");
				M("Member_integral")->data($info)->add();

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
			if(!empty($member_id)) {
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
