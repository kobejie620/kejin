<?php

namespace Wap\Controller;

use Think\Controller;

class NotifyController extends Controller {

    public function index() {
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            $data = array();
            $data['OpenId'] = $notifyInfo['openid'];                //用户openid
            $data['partner'] = $notifyInfo['mch_id'];               //商户ID
            $data['AppId'] = $notifyInfo['appid'];                  //公众号id
            $data['IsSubscribe'] = $notifyInfo['is_subscribe'];     //是否关注
            $data['NonceStr'] = $notifyInfo['nonce_str'];           //随机串
            $data['sign'] = $notifyInfo['sign'];                    //签名

            $data['out_trade_no'] = $notifyInfo['out_trade_no'];        //商户订单号
            $data['transaction_id'] = $notifyInfo['transaction_id'];    //微信订单号
            $data['bank_type'] = $notifyInfo['bank_type'];              //支付银行
            $data['total_fee'] = $notifyInfo['total_fee'];              //总价格
            $data['product_fee'] = $notifyInfo['crash_fee'];            //现金价

            $data['add_time'] = date("Y-m-d H:i:s");                //添加时间
            $data['time_end'] = $notifyInfo['time_end'];            //最后支付时间

            header('Content-Type:text/xml; charset=utf-8');

            M()->startTrans();
            if (false === M('StoreNotify')->add($data)) {
                $return = array();
                $return['return_code'] = 'ERROR';
                $return['return_msg'] = 'ADD DATA ERROR';
                exit(xml_encode($return));
            }

            /**
             * 判断支付订单的类型 
             * 2 开头的为积分订单 
             * 1 开头的是普通订单
             */
            $order_data = array();
            $order_data['status'] = 2;
            $order_data['total_fee'] = $notifyInfo['total_fee'];
            $order_data['trade_no'] = $notifyInfo['transaction_id'];
            $order_data['pay_status'] = 1;
            $order_data['real_amount'] = $notifyInfo['total_fee'] / 100;
            $order_data['pay_date'] = get_now_date();

            $order_type = intval(substr($data['out_trade_no'], 0, 1));
            if ($order_type === 2) {
                $result = M('StoreCredit')->where(array('credit_no' => $notifyInfo['out_trade_no']))->save($order_data);
            } else if ($order_type == 1) {
                $result = M('StoreOrder')->where(array('order_no' => $notifyInfo['out_trade_no']))->save($order_data);

                /* 增加到门店的折扣钱 by zenglingwen */
                $discount_data = array();
                $info = M('StoreOrder')->where(array('order_no' => $notifyInfo['out_trade_no']))->find();
                $sid = M('Member')->where(array('openid' => $notifyInfo['openid']))->find();
                $discount_data['create_time'] = get_now_date(); //门店折扣价钱创建时间      
                $discount_data['status'] = 6;
				
				//绑定会员
				if($info['memberid'] === $sid['member_id']) {
					$member = M('Member')->where(array('id' => $info['memberid']))->find();
                    $discount_data['memberid'] = $info['memberid'];
                    $discount_data['member_name'] = $member['nickname'];
                    $discount_data['phone'] = $member['phone'];
                    $discount_data['price'] = $info['member_discount'];
					$discount_data['type'] = 3 ;
                    $discount_data['order_id'] = $info['id']; //订单id
					if($info['member_discount'] > 0) {
						$res1 = M('Guide_discount')->add($discount_data);
						//把购买记录写到导购员成绩记录表
					}
					
//					if ($res1) {
//						//把提成写到导购员表
//						$str['tixianprice'] = $member['tixianprice'] + $info['member_discount'];
//						M('Member')->where(array('id' => $info['memberid']))->save($str);
//					}
				}
				
				//只绑定导购员，没绑定门店
                if ($sid['type'] == 1) {
                    $guide = M('Guide')->where(array('guide_num' => $sid['guide_num']))->find();
                    $discount_data['guide_num'] = $guide['guide_num'];
                    $discount_data['guide_name'] = $guide['guide_name'];
                    $discount_data['phone'] = $guide['phone'];
                    $discount_data['type'] = 1;
                    $discount_data['price'] = $info['guide_discount'];
                    $discount_data['order_id'] = $info['id']; //订单id
					if($info['guide_discount'] > 0) {
						$res1 = $discount = M('Guide_discount')->add($discount_data);
						//把购买记录写到导购员成绩记录表
					}
					
//					if ($res1) {
//						//把提成写到导购员表
//						$str['tixianprice'] = $guide['tixianprice'] + $info['guide_discount'];
//						M('Guide')->where(array('guide_num' => $sid['guide_num']))->save($str);
//					}
                    
                }
				
				//既绑定了导购员，又绑定了门店
                if ($sid['type'] == 2) {
                    $guide = M('Guide')->where(array('guide_num' => $sid['guide_num']))->find();
                    $myinfo['guide_num'] = $guide['guide_num'];
                    $myinfo['guide_name'] = $guide['guide_name'];
                    $myinfo['phone'] = $guide['phone'];
                    $myinfo['price'] = $info['guide_discount'];
                    $myinfo['type'] = 1;

                    $sid_number = M('Stores')->where(array('id' => $sid['sid']))->find();
                    $mydata['sid'] = $sid['sid'];
                    $mydata['number'] = $sid_number['number'];
                    $mydata['name'] = $sid_number['name'];
                    $mydata['phone'] = $sid_number['phone'];
                    $mydata['price'] = $info['order_discount'];
//                    $mydata['type'] = 2;

                    $myinfo['status'] = $mydata['status'] = 6;
                    $myinfo['create_time'] = $mydata['create_time'] = get_now_date(); //折扣价钱创建时间
                    $myinfo['order_id'] = $mydata['order_id'] = $info['id']; //订单id
					if($info['order_discount'] > 0) {
						M('Discount')->add($mydata);
					}
					if($info['order_discount'] > 0) {
						$res1 = M('Guide_discount')->add($myinfo);
					}

                    //把购买记录写到导购员成绩记录表
//                    if ($res1) {
//                        //把提成写到导购员表
//                        $str['tixianprice'] = $guide['tixianprice'] + $info['guide_discount'];
//                        M('Guide')->where(array('guide_num' => $sid['guide_num']))->save($str);
//                    }
                }
                
                if ((!empty($sid['sid']) && (empty($sid['type'])))) {
					$discount_data['price'] = $info['order_discount'];
					$discount_data['sid'] = $sid['sid'];
                    $sid_number = M('Stores')->where(array('id' => $sid['sid']))->find();
                    $discount_data['number'] = $sid_number['number'];
                    $discount_data['name'] = $sid_number['name'];
                    $discount_data['phone'] = $sid_number['phone'];
                    $discount_data['order_id'] = $info['id']; //订单id
					if($info['order_discount'] > 0) {
						$discount = M('Discount')->add($discount_data);
					}
                    
                }
            }
            if ($result !== false) {
                M()->commit();
                $return = array();
                $return['return_code'] = 'SUCCESS';
                $return['return_msg'] = '';
                //微信卡卷核销                
                if (!empty($info['wechat_card'])) {
                    $w_card = (array) json_decode($info['wechat_card']);
                    $wechat = new \Wechat\Model\WechatCardModel();
                    $consumeCard = $wechat->consumeCardCode($w_card['user_card_code']);
                    if ($consumeCard) {
                        M('WechatCardReceive')->where(array("user_card_code" => $w_card['user_card_code'], "card_id" => $w_card['card_id']))->setField("status", 1);
                    }
                }
                exit(xml_encode($return));
            }
            M()->rollback();
        }
    }

}
