<?php

namespace Shop\Controller;

/**
 * 我的订单控制器
 *
 * @author Zoe
 * @date 2014-12-11;
 */
class CreditOrderController extends ShopController {

    protected $_bind_model = 'StoreCredit';

    public function _before_index() {
        //设置排序，按照订单生成时间，降序
        val('_order', 'create_date');
        val('_sort', 1);
    }

    protected function _index_list_filter(&$model, &$map) {
        // 支付状态为-1时 显示全部订单
        $payStatus = I('get.pay_status', -1, 'trim,intval');
        $sendStatus = I('get.send_status', '', 'trim,intval');

        $map['openid'] = $this->openid;
        if ($payStatus !== -1) {
            $map['pay_status'] = $payStatus;
            $map['send_status'] = $sendStatus;
        } else {
            unset($map['pay_status']);
        }
        //获取查询字段
        $words = I('get.search');
        $order_ids = array();
        if (!empty($words)) {
            if (is_numeric($words)) {
                $map['credit_no'] = array('like', array("%{$words}%"));
            } elseif (is_string($words)) {
                //在产品快照中寻找订单id
                $products = M("StoreCreditProduct")->field("credit_id")->where(array('name' => array('like', array("%{$words}%"))))->select();
                //根据订单id筛选出属于当前用户的订单
                foreach ($products as $val) {
                    $id[] = M("StoreCredit")->field("id")->where(array("product_id" => $val['id']))->select();
                    foreach ($id as &$value) {
                        $order_ids[] = (int) $value;
                    }
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
            //微信支付处理
            if ($val['pay_type'] == 1) {
                $val['notify'] = "pay"; //需要支付
            }
            //获取订单的产品信息
            $val['products'] = M("StoreCreditProduct")->field('name,logo')->where(array('credit_id' => $val['product_id']))->find();
        }
    }

    /**
     * 订单详情页，未支付的订单直接进入支付页面
     * 
     * @author Zoe
     * @date 2014-12-12
     */
    public function detail() {
        $this->assign('ptitle', '订单详情');
        //根据订单号获取数据
        $credit_id = I('get.credit_id', 0, "intval");
        //1、获取订单信息
        $info = M("StoreCredit")->where(array('id' => $credit_id))->find();
        //2、获取产品快照
        $productInfo = M("StoreCreditProduct")->where(array('credit_id' => $credit_id))->find();
        $info['product_name'] = $productInfo['name'];
        $info['logo'] = $productInfo['logo'];
        $this->assign("info", $info);
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
            $result = M('StoreCredit')->save($data);
            if ($result !== false) {
                $this->ajaxReturn($data, 'JSON');
            } else {
                $this->error('操作失败，请稍后再试！');
            }
        }
    }

}
