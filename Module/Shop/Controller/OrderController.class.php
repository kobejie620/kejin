<?php

namespace Shop\Controller;

/**
 * 订单管理控制器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/22 10:52:50
 */
class OrderController extends ShopController {

    /**
     * 订单列表过滤
     * @param type $data
     */
    protected function _index_data_filter(&$data) {
        foreach ($data as &$row) {
            $map = array('order_id' => $row['id']);
            $row['list'] = M('StoreOrderProduct')->where($map)->select();
        }
    }

    /**
     * 订单准备页面，选地址，选快递...
     * 
     * @author Zoe
     * @date 2014-12-11
     */
    public function check() {
        $car_id = I('get.check', array(), 'trim');
        if (!empty($car_id)) {
            /* 选择的购物车记录ID */
            $car_id = I('get.check', array());
            /* 全部购物车记录ID */
            $_car_id = I('get.car_id', array());
            /* 对应购物车记录数量 */
            $_number = I('get.number', array());
            /* 数据扁平化 */
            $_selected = array();
            foreach ($_car_id as $key => $id) {
                in_array($id, $car_id) && isset($_number[$key]) && $_selected[$id] = $_number[$key];
            }
            /* 购物车提交，直接GET购物车记录ID，以英文逗号分割 */
            $list = M('StoreOrderCar')->where(array('id' => array('in', $car_id)))->select();
            //更新购物车的数量，暂不写入库
            foreach ($list as &$vo) {
                isset($_selected[$vo['id']]) && $vo['num'] = $_selected[$vo['id']];
            }
            unset($_selected, $_car_id, $_number);
        } else {
            /* 直接购买，直接GET接收数据 */
            $item = array();
            $item['param'] = I("get.param");
            $item['price'] = I("get.price");
            $item['num'] = I("get.num");
            $item['cid'] = I("get.cid");
            $item['id'] = 0;
            $memberid = I("get.memberid");
            $list = array();
            $list[] = $item;
            $this->assign("memberid", $memberid);
        }

        /**
         * 数据为空时，返回错误
         */
        if (empty($list)) {
            $this->error('订单提交失败，请稍候再试！');
        }
        //微信卡卷
        $wechat = new \Wechat\Model\WechatCardModel();
        //选择卡卷
        $this->assign('kajuan', $wechat->chooseCard());
        /* 用户是否有卡券 */
        $is_kq = M('WechatCardReceive')->where(array('openid' => $this->openid, 'status' => 2))->find();
        if (empty($is_kq)) {
            $this->assign('is_kq', 1);
        }
        /**
         * 收货地址处理
         */
        $map = array();
        $map['openid'] = $this->openid;
        $addressid = I("get.addressid", 0, 'trim,intval');
        if (empty($addressid)) {
            $map['is_default'] = 2; //默认地址
        } else {
            $map['id'] = $addressid; //选中地址
        }
        $address = M("MemberAddress")->where($map)->find();

        $free_express = 0;
        $times = array();
        foreach ($list as $val) {
            //获取商品信息
            $product = M("StoreProduct")->where(array('id' => $val['cid']))->find();
            $arr = explode('_split_', $val['param']);
            $product["all_params"][$arr[0]] = $arr[1];
            $product['num'] = $val['num'];
            $product['price'] = $val['price'];
            $products[] = $product;
            //检查商品的状态是否为下架
            if ($product['status'] == '1') {
                $this->error($product['name'] . '不能购买，此商品已经下架！');
            }
            //检测库存是否充足  add by tanglinjun 2015-01-20
            $store = get_product_store($val['cid'], $val['param']);
            if (empty($store)) {
                $store = M("StoreProduct")->where(array('id' => $val['cid']))->getField("store_nums");
            }
            $surplus = $store - $val['num'];
            if ($surplus < 0) {
                R('Wap/Error/show', array('库存不足', '请浏览其他产品！'));
            }

            //检查产品购买次数是否符合要求 add by tanglinjun 2015-01-27
            $productInfo = M("StoreProduct")->field("times_limit,free_express")->where(array("id" => $val['cid']))->find();
            if ($productInfo['times_limit'] == '0') {
                R('Wap/Error/show', array('本产品暂时不能购买', '请浏览其他产品！'));
            } elseif ($productInfo['times_limit'] > 0) {
                //记录商品次数
                $times[$val['cid']] += $val['num'];
            }

            //在多产品订单中，有一个产品免邮，那么都免邮。
            if ($productInfo['free_express'] == '1') {
                $free_express = 1;
            }
        }

        //修复产品未选属性导致商品购买限制失效的bug add by tanglinjun 2015-01-29
        foreach ($times as $key => $val) {
            //1、统计当前会员购买此产品的次数
            $buyNums = M("StoreOrderProduct")->where(array("product_id" => $key, "openid" => $this->openid, "is_pay" => array('in', '0,1')))->sum("product_nums");

            //2、已有的购买次数+当前购买次数
            $nowNums = $buyNums + $val;
            if ($nowNums > $productInfo['times_limit']) {
				$this->error('超过本产品的购买次数');
//				('Wap/Error/show', array('超过本产品的购买次数', '请浏览其他产品！'));
            }
        }

        //输出产品列表
        $this->assign("products", $products);
        $this->assign('list', $list);
        //获取快递信息
        $deliverys = M("StoreDelivery")->where(array('status' => 2))->select();
        $this->assign('deliverys', $deliverys);
        //总金额
        $this->assign('total_price', $this->_getTotal($list));
        //是否免邮
        $this->assign("free_express", $free_express);
        //收货地址
        $this->assign("address", $address);
        $this->assign("ptitle", "提交订单");
        $this->display();
    }

    /**
     * 计算数据
     * @param type $data
     * @return type
     */
    private function _getTotal(&$data) {
        $total_price = 0;
        foreach ($data as &$row) {
            $total_price += $row['num'] * $row['price'];
        }
        return number_format($total_price, 2, '.', '');
    }

    /**
     * 添加订单,并添加产品快照，处理产品销量
     * 
     * @author Zoe
     * @date 2014-12-11
     */
    public function show() {

        if (IS_POST) {
            /* 创建订单 */
            $data = array();
            $data['openid'] = $openid = $this->openid;
            /* 支付类型 */
            $data['pay_type'] = I('post.pay_type', '1');
            /* 快递配送 */
            $fid = I('post.freight', 0, 'intval');
            if (empty($fid)) {
                //免邮处理
                $data['freight'] = "免邮";
                $data['pay_freight'] = "0";
            } else {
                $deliverys = M("StoreDelivery")->where(array('id' => $fid))->find();
                $data['freight'] = $deliverys['name'];
                $data['pay_freight'] = $deliverys['firstprice'];
            }

            $list = json_decode(I('post.list', '[]', 'trim'), true);

            if (empty($list)) {
                $this->error('订单提交失败，请稍候后再试');
            }
            $data['order_amount'] = $this->_getTotal($list);

            // 收货人信息
            //绑定会员的id
            $memberid = I('post.memberid');
            $data['accept_name'] = I('post.accept_name');
            $data['country'] = I('post.country');
            $data['province'] = I('post.province');
            $data['city'] = I('post.city');
            $data['area'] = I('post.area');
            $data['address'] = I('post.address');
            $data['phone'] = I('post.phone');
            $data['postcode'] = I('post.postcode');
            //代金卷code
            $wechat_code['card_id'] = I('post.code_card_id');
            $wechat_code['user_card_code'] = I('post.user_card_code');
            if (!empty($wechat_code['card_id']) && !empty($wechat_code['user_card_code'])) {
                $card = M('WechatCard')->where(array('card_id' => $wechat_code['card_id']))->find();
                $wechat_code['reduce_cost'] = $card['reduce_cost'];
                $wechat_code['least_cost'] = $card['least_cost'];
                $data['wechat_card'] = json_encode($wechat_code);
            }
            // 用户留言
            $data['post_script'] = I('post.post_script');
            /* 循环列表，获得门店折扣的钱数,存入订单 by zenglingwen */
            $order_discount = 0;
            //循环列表，获得导购员折扣的钱数，存入订单 
            $guide_discount = 0;
            //循环列表，获得会员折扣的钱数，存入订单 
            $member_discount = 0;
            foreach ($list as &$row) {
                /* 购买限制 */

                //获得门店折扣的钱数
                $discount = M('StoreProduct')->field('times_limit,discount,guide_discount,member_discount')->where(array('id' => $row['cid']))->find();
                if ($discount['times_limit'] !== '-1') {
                    if ($discount['times_limit'] == '0') {
                       $this->error('此产品限制！', U('Shop/Index/edit/id/' . $row['cid']));
                    } elseif ($discount['times_limit'] > 0) {
                        //记录商品次数
                        $buyNums = M("StoreOrderProduct")->where(array("product_id" => $row['cid'], "openid" => $this->openid, "is_pay" => array('in', '0,1')))->sum("product_nums");
                        if ($buyNums > $discount['times_limit']) {
                            $this->error('购买产品数量超出限制！', U('Shop/Index/edit/id/' . $row['cid']));
                        }
                    }
                }
                $order_discount += $row['num'] * $row['price'] * $discount['discount'] / 100;
                //导购员折扣的钱数
                $guide_discount += $row['num'] * $row['price'] * $discount['guide_discount'] / 100;
                //会员折扣的钱数
                $member_discount += $row['num'] * $row['price'] * $discount['member_discount'] / 100;
            }
            //获得门店折扣的钱数
            $data['order_discount'] = $order_discount;
            //导购员折扣的钱数
            $data['guide_discount'] = $guide_discount;
            //会员折扣的钱数
            $data['member_discount'] = $member_discount;
            /* 存入门店的id by zenglingwen */
            $member_sid = M('Member')->field('sid , guide_num')->where(array('openid' => $data['openid']))->find();
            $data['store_id'] = $member_sid['sid'];
            $data['memberid'] = $memberid;
            $data['guide_num'] = $member_sid['guide_num'];
            M()->startTrans();
            //防止表单重复提交
            $result = $this->_save($data, D('StoreOrder'));
            if ($result['status']) {
                $order_id = $result['data']['id'];
                //定义订单号
                $data = array();
                //前面带上1 代表普通订单
                $data['order_no'] = "1" . date('ymdH') . str_pad("{$order_id}", 4, '0', STR_PAD_LEFT);
                $result['order_no'] = $data['order_no'];
                $data['id'] = $order_id;
                $result2 = $this->_save($data, D('StoreOrder'));
                if ($result2['status']) {
                    //写入订单详情
                    $result_list = true;
                    $discount = 0; //存入快照的门店折扣金额
                    $discount1 = 0; //存入快照的导购员折扣金额
                    $discount2 = 0; //存入快照的会员折扣金额
                    foreach ($list as $prot) {
                        $product_info = M('StoreProduct')->where(array('id' => $prot['cid']))->find();
                        $data = array();
                        $data['order_id'] = $order_id;
                        $data['product_id'] = $prot['cid'];
                        $data['openid'] = $this->openid;
                        $data['product_name'] = $product_info['name'];
                        $data['img'] = $product_info['logo'];
                        $data['weight'] = $product_info['weight'];
                        $data['params'] = $prot['param'];
                        $data['product_params'] = $product_info['params'];
                        $data['product_price'] = $prot['price'];
                        $data['product_nums'] = $prot['num'];

                        //获得门店的折扣金额，存入快照by zenglingwen
                        $discount += $prot['num'] * $prot['price'] * $product_info['discount'] / 100;
                        $data['discount_price'] = $discount;

                        //获取导购员的折扣金额，存入快照
                        $discount1 += $prot['num'] * $prot['price'] * $product_info['guide_discount'] / 100;
                        $data['guide_discount_price'] = $discount1;

                        //获取门店的折扣金额，存入快照
                        $discount2 += $prot['num'] * $prot['price'] * $product_info['member_discount'] / 100;
                        $data['member_discount_price'] = $discount2;

                        $r1 = $this->_save($data, M('StoreOrderProduct'));

                        //更新购物车
                        if ($prot['id'] == 0) {
                            $r2 = 1; //所有直接都买的都跳过购物车状态修改  luoshaobo 2015-04-29
                        } else {
                            $r2 = M('StoreOrderCar')->where(array('id' => $prot['id']))->setField('status', 2);
                        }

                        //更新库存与销量
                        $tmpParams = json_decode($product_info['params'], true);
                        $tmpStr = str_replace('-', '/', $prot['param']) . "_split_store";
                        $tmpParams[$tmpStr] = $tmpParams[$tmpStr] - $prot['num'];
                        $tmpParams = json_encode($tmpParams);
                        $r3 = M('StoreProduct')->where(array('id' => $prot['cid']))->setInc('sale', $prot['num']);
                        $r5 = M("StoreProduct")->where(array('id' => $prot['cid']))->setDec('store_nums', $prot['num']); // 库存减少
                        $r4 = M("StoreProduct")->where(array('id' => $prot['cid']))->setField('params', $tmpParams);
                        if (!$r1['status'] or $r2 === FALSE or $r3 === FALSE or $r4 === FALSE or $r5 === FALSE) {
                            $result_list = false;
                            break;
                        }
                    }

                    if ($result_list) {
                        M()->commit();
                        M('WechatCardReceive')->where(array('openid' => $this->openid, "card_id" => $wechat_code['card_id'], "user_card_code" => $wechat_code['user_card_code']))->setField("status", 3);             //代金卷设置成正在使用 
                        $this->success('订单提交成功', U('/shop/pay/order_' . $order_id));
                        die();
                    }
                }
            }
            M()->rollback();
            $this->error('订单提交失败，请稍候再试！');
        } else {
            $order_id = I('get.order_id', 0, 'intval');
            //1、获取订单信息
            $orderInfo = M("StoreOrder")->where(array('id' => $order_id))->find();
            //不能取消
            $nocan = M("StoreOrderProduct")->where(array('order_id' => $order_id))->field('product_id')->select();
            foreach ($nocan as $key => $va) {
                $conid[] = $va['product_id'];
            }
            $map['miaosha|xianshi'] = 1;
            $map['id'] = array('in', $conid);
            $is_can = M('StoreProduct')->where($map)->field('id')->select();
            if (!empty($is_can)) {
                $this->assign('nocan', 1);
            }
            if (!empty($orderInfo['wechat_card'])) {
                $w_card = (array) json_decode($orderInfo['wechat_card']);
                $wechat_card = M('WechatCard')->where(array('card_id' => $w_card['card_id']))->field("reduce_cost,title")->find();
                $orderInfo['yhjtitle'] = $wechat_card['title'];
                $orderInfo['yhj'] = $wechat_card['reduce_cost'] / 100;
                $orderInfo['promotions'] += $orderInfo['yhj'];
            }
            $orderInfo['payprice'] = intval(round($orderInfo['order_amount'] + $orderInfo['pay_freight'] - $orderInfo['promotions'], 2) * 100);
            $this->assign("orderInfo", $orderInfo);

            //2、获取产品快照
            $productInfo = M("StoreOrderProduct")->where(array('order_id' => $order_id))->select();
            $this->assign("productInfo", $productInfo);
            !$order_id && $this->redirect('Shop/Index/index');

            //微信支付处理
            $notify_url = to_domain(U('Wap/Notity/index'));
            $this->assign('paydata', json_encode($data));
            $this->assign('notify_url', $notify_url);

            $this->assign("ptitle", "订单详情");
            $this->display();
        }
    }

    /**
     * 未支付请求的参数信息
     * 
     * @author tanglinjun
     * @date 2015-02-010
     */
    public function payInfo() {
        if (IS_POST) {
            $order_no = I("post.id", 0, "intval");
            if (!$order_no) {
                $this->error("数据错误");
            }
            //判断支付订单的类型 
            $check = intval(substr($order_no, 0, 1));

            if ($check == 2) {
                //***************积分订单支付************//
                //1、获取订单信息
                $creditInfo = M("StoreCredit")->where(array('credit_no' => $order_no))->find();
                //支付总价
                $creditInfo['payprice'] = intval(round($creditInfo['order_amount'] + $creditInfo['pay_freight'] - $creditInfo['promotions'], 2) * 100);

                //微信支付处理
                $products = M('StoreCreditProduct')->where(array('id' => $creditInfo['product_id']))->find();
                $out_trade_no = $creditInfo['credit_no'];         //订单号
                $body = $products['name'];           //body
                $total_fee = $creditInfo['payprice'];            //支付总价
            } else if ($check == 1) {
                //***************普通订单支付************//
                //1、获取订单信息
                $orderInfo = M("StoreOrder")->where(array('order_no' => $order_no))->find();
                //支付总价
                if (!empty($orderInfo['wechat_card'])) {
                    $w_card = (array) json_decode($orderInfo['wechat_card']);
                    $reduce_cost = M('WechatCard')->where(array('card_id' => $w_card['card_id']))->getField("reduce_cost");
                }
                $orderInfo['payprice'] = intval(round($orderInfo['order_amount'] + $orderInfo['pay_freight'] - $orderInfo['promotions'], 2) * 100) - $reduce_cost;
                //微信支付处理
                $products = M('StoreOrderProduct')->where(array('order_id' => $orderInfo['id']))->select();
                $out_trade_no = $orderInfo['order_no'];         //订单号
                $body = $products[0]['product_name'];           //body
                $total_fee = $orderInfo['payprice'];            //支付总价
            }

            //2、获取发起支付的参数
            $wechatPay = $this->getInstanceWechatPay();
            $notify_url = U('Wap/Notify/index', '', true, true); //支付回调通知url
            $prepay_id = $wechatPay->getPrepayId($this->openid, $body, $out_trade_no, $total_fee, $notify_url, "JSAPI");
            if (empty($prepay_id)) {
                $this->error('支付调用失败，' . $wechatPay->errMsg);
            }
            $payParams = $wechatPay->createMchPay($prepay_id);
            $payParams['status'] = 1;
            $this->ajaxReturn($payParams, 'JSON');
        }
    }

    /**
     * 取消订单，status置1
     * @author Zoe<tanglinjun>
     * @date 2014-12-19
     */
    public function cancel() {
        //获取要删除的id
        if (IS_POST) {
            $id = I('post.id', 0, "intval");
            if (empty($id)) {
                $this->error('数据参数错误');
            }

            M()->startTrans();
            //更新快照表状态
            $r3 = M("StoreOrderProduct")->where(array("order_id" => $id))->setField("is_pay", 2);
            $up_dis = array();
            $up_dis['change_time'] = get_now_date();
            $up_dis['status'] = 5;
            $discount = M("Discount")->where(array('order_id' => $id))->setField($up_dis); //退款更改门店折扣卡的状态，by zenglingwen
            $guide_discount = M("Guide_discount")->where(array('order_id' => $id))->setField($up_dis); //退款更改导购员折扣卡的状态，by chenrongbin
            $member_discount = M("Member_discount")->where(array('order_id' => $id))->setField($up_dis); //退款更改会员折扣卡的状态，by zenglingwen
            //更新库存与销量
            $order_products = M("StoreOrderProduct")->field("id,product_id,product_nums,params")->where(array('order_id' => $id))->select();
            foreach ($order_products as $val) {
                //1、解析json格式的属性参数
                $tmpParams = M("StoreProduct")->where(array('id' => $val['product_id']))->getField("params");
                $tmpParams = json_decode($tmpParams, true);
                if (!empty($tmpParams)) {
                    //2、根据产品快照里的params与product_nums修改产品库存吧
                    $str = $val['params'] . "_split_store";
                    $tmpParams[$str] = $tmpParams[$str] + $val['product_nums'];
                    //3、然后根据产品Id更新产品的params参数
                    $tmpParams = json_encode($tmpParams);
                    $r4 = M("StoreProduct")->where(array('id' => $val['product_id']))->setInc('store_nums', $val['product_nums']); // 取消订单，库存增加
                    $r2 = M("StoreProduct")->where(array('id' => $val['product_id']))->setField("params", $tmpParams);
                }
                if ($r2 === false) {
                    break;
                }
            }
            $pay_info = M('StoreOrder')->where(array('id' => $id))->find();
            //若有选择卡卷，则将卡卷状态恢复
            if (!empty($pay_info['wechat_card'])) {
                $w_card = (array) json_decode($data['wechat_card']);
                M("WechatCardReceive")->where(array("card_id" => $w_card['card_id'], "user_card_code" => $w_card['user_card_code']))->setField("status", 2);
            }
            //获取商城自动退款配置
            if ($pay_info['pay_status'] == 1) {
                $refund = M("Store")->where(array("id" => 1))->getField("is_refund");
                if (!empty($refund)) {
                    //退钱
                    $r1 = D('Store/StoreOrder')->refund($id, $this->getInstanceWechatPay());
                } else {
                    $r1 = false;
                }
            } else {
                $data = array();
                $data['cancel_date'] = get_now_date();
                $data['id'] = $id;
                $data['status'] = '3';
                $r1 = M("StoreOrder")->save($data);
            }
            if (false !== $r1 && $r3 !== false && $r2 !== false) {
                M()->commit();

                $this->success("取消订单成功！", U("Shop/MyOrder/index"));
            } else {
                M()->rollback();
                $this->error("取消订单失败，请稍候再试");
            }
        }
    }

    public function usecard() {
        if (IS_POST) {
            $card_id = I('post.card_id');
            $en_code = I('post.encrypt_code');
            $total_price = I('post.total_price');
            $wechat = $this->getInstanceWechat();
            $code = $wechat->decryptCardCode($en_code);    //对微信传过来的encrypt_code解码
            $where = array('openid' => $this->openid, "card_id" => $card_id, "user_card_code" => $code['code']);
            $cardReceive = M('WechatCardReceive');
            $Card = $cardReceive->where($where)->field("card_id,user_card_code,status")->find();   // 找到要使用的卡卷
            if ($Card['status'] == 3) {
                $res['info'] = "该卡卷已被其他订单选用，请选择别的卡卷";
                $res['status'] = 3;
                $this->ajaxReturn($res);
                die;
            }
            $card = M('wechatCard')->where(array('card_id' => $card_id))->field("card_id,reduce_cost,least_cost")->find();
            if ($total_price >= $card['least_cost'] / 100) {
                $res['status'] = 1;
                $res['last_price'] = $total_price - $card['reduce_cost'] / 100;
                $res['card_id'] = $Card['card_id'];
                $res['user_card_code'] = $Card['user_card_code'];
                $res['card_title'] = M('wechatCard')->where(array('card_id' => $Card['card_id']))->getField("title");
                $this->ajaxReturn($res);
            } else {
                $res['status'] = 2;
                $res['info'] = "对不起，您使用的代金卷不满足最低消费金额，请重新选择";
                $this->ajaxReturn($res);
            }
        }
    }

}
