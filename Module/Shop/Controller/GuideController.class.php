<?php

namespace Shop\Controller;

/**
 * 导购员的订单控制器
 *
 * @author Zoe
 * @date 2014-12-11;
 */
class GuideController extends ShopController {

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'StoreOrder';

    /**
     * 列表显示过滤方法
     * @param type $model
     * @param type $map
     */
    protected function _index_filter(&$model, &$map) {
        //设置排序，按照订单生成时间，降序
        val('_order', 'create_date');
        val('_sort', 1);

        //只显示该导购员的订单
		$guide = M('Guide')->where(array('openid' => $this->openid))->find();
        //$map['guide_num'] = $guide['guide_num'];
		$map['guide_num|openid'] =array($guide['guide_num'],$this->openid,'_multi'=>true);
//		$map['_logic'] = 'OR';
//		$map['openid'] = $this->openid ;
		
        // 支付状态为-1时 显示全部订单
        if (!I('get._getcode_', false)) {
            $payStatus = I('get.pay_status', -1, 'trim,intval');
            $sendStatus = I('get.send_status', '', 'trim,intval');

            if ($payStatus !== -1) {
                $map['pay_status'] = $payStatus;
                $map['send_status'] = $sendStatus;
            } else {
                unset($map['pay_status']);
            }
        }

        //获取查询字段
        $words = I('get.search', '', 'trim');

        if (!empty($words)) {
            if (is_numeric($words)) {
                $map['order_no'] = array('like', array("%{$words}%"));
            } elseif (is_string($words)) {
                //在产品快照中寻找订单id
                $map = array('product_name' => array('like', array("%{$words}%")));
                $products = M("StoreOrderProduct")->field("order_id")->where($map)->select();
                //根据订单id筛选出属于当前用户的订单
                $order_ids = array();
                foreach ($products as $val) {
                    $order_ids[] = (int) $val['order_id'];
                }
                $map['id'] = array('in', $order_ids);
            }
        }
    }

    /**
     * 
     * @param \Think\Model $model
     * @param type $map
     */
    protected function _index_data_filter(&$data, &$model) {
        foreach ($data as &$val) {
            //获取订单的产品信息
            $val['products'] = M("StoreOrderProduct")->field('product_name,product_price,product_nums,img,params')->where(array('order_id' => $val['id']))->select();
            $val['nums'] = 0;
            foreach ($val['products'] as &$value) {
                $val['nums'] += intval($value['product_nums']);
            }
        }
    }

    /**
     * 订单详情页，未支付的订单直接进入支付页面
     * @author Zoe
     * @date 2014-12-12
     */
    public function detail() {
        $this->assign('ptitle', '订单详情');
        //根据订单号获取数据
        $order_id = I('get.order_id', 0, "intval");
        //1、获取订单信息
        $orderInfo = M("StoreOrder")->where(array('id' => $order_id))->find();
        $this->assign("orderInfo", $orderInfo);
        //2、获取产品快照
        $productInfo = M("StoreOrderProduct")->where(array('order_id' => $order_id))->select();
        $this->assign("productInfo", $productInfo);

        $this->display();
    }

    /**
     * 确认收货
     */
    public function confirmSend() {
        if (IS_POST) {
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
