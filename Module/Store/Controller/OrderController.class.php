<?php

namespace Store\Controller;

/**
 * 订单管理控制器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 14:00:00
 */
class OrderController extends StoreController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '订单管理';

    /**
     * 设置模块可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '订单列表',
        'edit' => '订单修改',
        'reduce' => '订单改价',
        'discount_info' => '门店折扣的处理',
    );

    /**
     * 设置菜单可配的操作节点
     * @var type 
     */
    public $menu = array(
        'index' => '订单列表',
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'StoreOrder';

    /**
     * 查看订单信息
     */
    public function _before_index() {
        val('_sort', 1);
    }

    protected function _index_data_filter(&$data, $model) {
        foreach ($data as $key => &$value) {
            $info = M('Stores')->where(array('id' => $value['store_id']))->find();
            if (!empty($info)) {
                $value['store_id'] = $info['name'];
            }
        }
    }

    //index前置$map条件方法
    protected function _index_filter($model, &$map) {
        if (session('user.role') == '24') {//登录权限为门店
            $map['store_id'] = array('eq', session('user.number'));
        }
    }

    /**
     * 查询过滤
     */
    protected function _list_filter(&$model, &$map) {
        $map["order_no"] = array("like", "%" . I("get.order_no") . "%");
        $map["accept_name"] = array("like", "%" . I("get.accept_name") . "%");
        $map["phone"] = array("like", "%" . I("get.phone") . "%");
    }

    /**
     * 对form操作的数据进行补充
     * @param array $data
     */
    protected function _form_filter(&$model, &$data) {
        $id = I('get.id');
        $info = M('StoreOrder')->where(array('id' => $id))->find();
        if ($info['send_status'] == 1) {
            if (session('user.role') == '0' || session('user.role') == '1') {//登录权限为系统管理员
                $role = 0; /* 管理员 */
            }
            $this->assign('role', $role);
        }

        if (!IS_POST) {
            $data['trade_no'] = empty($data['trade_no']) ? '未支付' : $data['trade_no'];
            empty($data['freight_date']) && $data['freight_date'] = get_now_date();
            $data['freights'] = M('StoreDelivery')->where(array('status' => 2))->select();
            foreach ($data['freights'] as $fre) {
                if ($data['freight'] === $fre['name']) {
                    $data['freight_url'] = $fre['url'];
                }
            }
            $data['info'] = M('StoreOrderProduct')->where(array('order_id' => $data['id']))->select();
            foreach ($data['info'] as &$in) {
                $in['product'] = M('StoreProduct')->find($in['cid']);
            }
        }
        if ($_POST) {
            if (I('post.send_status')) {
                $list = M('StoreOrder')->where(array('id' => I('post.id')))->find();
                if ($list['pay_status'] == 0) {
                    $this->error('请用户付款后再进行发货操作！');
                }
            }
        }
    }

    /**
     * 数据保存成功，自动打开本窗口
     */
    protected function _form_success($model, $data) {
        $info = $model->find($data['id']);

        $result = FALSE;
        if (intval($info['pay_status']) === 1 && intval($info['status']) === 3) {
            //获取商城自动退款配置
            $refund = M("Store")->where(array("id" => 1))->getField("is_refund");
            if (!empty($refund)) {
                //自动退款
                $result = $model->refund($data['id'], $this->getInstanceWechatPay());
            } else {
                //手动退款
                $post_refund = I("post.action");
                if ($post_refund == "refund") {
                    $result = $model->refund($data['id'], $this->getInstanceWechatPay());
                }
            }
        }
        $url = url_filter();
        if (!$result) {
            $this->success('操作成功，重新加载中...', "javascript:$.form.close();$.form.load('{$url}')");
            return false;
        }
    }

    /**
     * 订单改价
     * @author Zoe
     * @date 2014-12-24
     */
    public function reduce() {
        $this->ptitle = "订单改价";
        //根据订单号来获取该订单的信息
        $order_id = I('get.id', 0, 'intval');
        $orderInfo = M("StoreOrder")->where(array('id' => $order_id))->find();
        $total_price = sprintf("%.2f", $orderInfo['order_amount'] + $orderInfo['pay_freight'] - $orderInfo['promotions']);
        $this->assign('total_price', $total_price);
        $this->assign("vo", $orderInfo);
        if (!$order_id) {
            $this->error("数据错误");
        }
        //改价只在编辑的时候出现
        if (IS_POST) {
            $promotions = I('post.promotions');
            //解决订单改价照成订单重复而不能支付的问题 add by zhong&tang 2015-02-10
            $data = array();
            $data['promotions'] = $promotions;
            $is_change = F($order_id);
            if (!$is_change) {
                F($order_id, 1);
                $data['order_no'] = $orderInfo['order_no'] . '1';
            } else {
                $data['order_no'] = intval($orderInfo['order_no']) + 1;
            }

            $result = M("StoreOrder")->where(array('id' => $order_id))->save($data);
            if ($result) {
                $this->success("订单改价成功！");
            } else {
                $this->error("订单改价失败！");
            }
        } else {
            $this->assign('ptitle', $this->ptitle);
            $this->display();
        }
    }

    /* discount状态更改，确定状态 */

    public function discount_info() {
        if (IS_POST) {
			//修改佣金状态
			$order_id= I('post.id');
			$result = M('Guide_discount')->where(array('order_id'=> $order_id))->save(array('status'=>1));
			//把提成写到导购员表
			if ($result) {
				//获取导购员的信息
				$store = M('Guide_discount')->where(array('order_id'=> $order_id))->find();
				$guide = M('Guide')->where(array('guide_num'=> $store['guide_num']))->find();
				
				$str['tixianprice'] = $guide['tixianprice'] + $store['price'];
				M('Guide')->where(array('guide_num' => $guide['guide_num']))->save($str);
			}
			//把提成写到member表
			$result1 = M('Member_discount')->where(array('order_id' => $order_id))->save(array('status'=>1));
			if($result1) {
				//获取导购员的信息
				$store = M('Member_discount')->where(array('order_id'=> $order_id))->find();
				$member = M('Member')->where(array('id'=> $store['memberid']))->find();
				
				$str['tixianprice'] = $member['tixianprice'] + $store['price'];
				M('Member')->where(array('id' => $store['memberid']))->save($str);
			}
            M('Discount')->where(array('order_id'=>I('post.id')))->save(array('status'=>1));
		}
	}
    /**
     * 确认收货
     */
    public function confirmSend() {
        if (IS_POST) {
			$order_id= I('post.id');
			//获取导购员佣金
			$discount = M('StoreOrder')->where(array('id' => $order_id))->find();
			//修改导购员佣金状态
			$res = M('Guide_discount')->where(array('order_id'=> $order_id))->save(array('status'=>1));
			//把提成写到导购员表
			if ($res) {
				//获取导购员的信息
				$store = M('Guide_discount')->where(array('order_id'=> $order_id))->find();
				$guide = M('Guide')->where(array('guide_num'=> $store['guide_num']))->find();
				
				$str['tixianprice'] = $guide['tixianprice'] + $discount['guide_discount'];
	
				M('Guide')->where(array('guide_num' => $guide['guide_num']))->save($str);
			}
			
			$res1 = M('Member_discount')->where(array('order_id' => $order_id))->save(array('status'=>1));
			//把提成写到member表
			if($res1) {
				//获取会员的信息
				$store = M('Member_discount')->where(array('order_id'=> $order_id))->find();
				$member = M('Member')->where(array('id'=> $store['memberid']))->find();
				
				$str['tixianprice'] = $member['tixianprice'] + $discount['member_discount'];
				M('Member')->where(array('id' => $store['memberid']))->save($str);
			}
			
			//修改导购员佣金状态
			M('Discount')->where(array('order_id'=> $order_id))->save(array('status'=>1));
			
            $data = array();
            $data['id'] = I('post.id');
            $data['accept_date'] = get_now_date();
            $data['send_status'] = 2;
            $result = M('StoreOrder')->save($data);
            if ($result !== false) {
                $this->ajaxReturn($data, 'JSON');
            } else {
                $this->error('操作失败，请稍后再试！');
            }
        }
    }
	
}
