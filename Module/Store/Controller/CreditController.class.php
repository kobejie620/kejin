<?php

namespace Store\Controller;

/**
 * 积分订单控制器
 *
 * @author tanglinjun
 * @date 2015-01-23
 */
class CreditController extends StoreController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '积分兑换';

    /**
     * 设置模块可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '兑换记录',
        'edit' => '编辑兑换',
    );

    /**
     * 设置可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '兑换记录'
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'StoreCredit';

    protected function _index_data_filter(&$data, &$model) {
        foreach ($data as &$val) {
            //处理收货人姓名
            if (empty($val['accept_name'])) {
                $val['accept_name'] = "-";
            }
            //处理微信昵称
            $val['nickname'] = M("WechatMember")->where(array("openid" => $val['openid']))->getField("nickname");
        }
    }

    /**
     * 对form操作的数据进行补充
     * @param array $vo
     */
    protected function _form_filter(&$model, &$vo) {
        if (!IS_POST) {
            //获取快递信息
            empty($vo['delivery_date']) && $vo['delivery_date'] = get_now_date();
            $vo['freights'] = M('StoreFreightCompany')->where(array('status' => 2))->select();
            foreach ($vo['freights'] as $fre) {
                if ($vo['freight'] === $fre['name']) {
                    $vo['freight_url'] = $fre['url'];
                }
            }
            //获取产品快照
            $info = M("StoreCreditProduct")->field("name,logo")->where(array("id" => $vo['product_id']))->find();
            $this->assign("info", $info);
        }
    }

    /**
     * 数据保存成功，自动打开本窗口
     * @param \Think\Model $model
     * @param type $data
     * @return boolean
     */
    protected function _form_success($model, $data) {
        $info = $model->find($data['id']);
        if ($info['total_credit'] > 0) {
            // 取消订单，返还积分
            D('Shop/MemberIntegral')->changeIntegral($info['openid'], "--" . $info['total_credit'], "取消兑换返还积分", false);
            $model->where(array('id' => $info['id']))->setField('total_credit', 0);
        }
        $result = false;
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
        $credit_id = I('get.id', 0, 'intval');
        $orderInfo = M("StoreCredit")->where(array('id' => $credit_id))->find();
        $total_price = sprintf("%.2f", $orderInfo['order_amount'] + $orderInfo['pay_freight'] - $orderInfo['promotions']);
        $this->assign('total_price', $total_price);
        $this->assign("vo", $orderInfo);
        if (!$credit_id)
            $this->error("数据错误");
        //改价只在编辑的时候出现
        if (IS_POST) {
            $promotions = I('post.promotions');
            //解决订单改价照成订单重复而不能支付的问题 add by zhong&tang 2015-02-10
            $data = array();
            $data['promotions'] = $promotions;
            $is_change = F($credit_id);
            if (!$is_change) {
                F($credit_id, 1);
                $data['credit_no'] = $orderInfo['credit_no'] . '1';
            } else {
                $data['credit_no'] = intval($orderInfo['credit_no']) + 1;
            }

            $result = M("StoreCredit")->where(array('id' => $credit_id))->save($data);
            if ($result) {
                $this->success("积分订单改价成功！");
            } else {
                $this->error("积分订单改价失败！");
            }
        } else {
            $this->assign('ptitle', $this->ptitle);
            $this->display();
        }
    }

}
