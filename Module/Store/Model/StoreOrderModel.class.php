<?php

namespace Store\Model;

use Think\Model;

/**
 * 订单操作模型
 */
class StoreOrderModel extends Model {

    /**
     * 退款处理
     * @param type $order_no 订单号|订单ID
     * @param type $wecathPay 微信支付SDK
     * @return boolean
     */
    public function refund($order_no, $wecathPay) {
        $info = $this->where(array('order_no|id' => $order_no))->find();
        if (empty($info)) {
            return false;
        }
        $result = true;
        if (intval($info['pay_status']) === 1 && empty($info['send_status']) && intval($info['status']) !== 4) {
            if (empty($info['out_refund_no'])) {
                $out_refund_no = "3" . date('ymdH') . str_pad("{$info['id']}", 4, '0', STR_PAD_LEFT);
            } else {
                $out_refund_no = $info['out_refund_no'];
            }
            $refund = $wecathPay->refund($info['order_no'], $info['trade_no'], $out_refund_no, $info['total_fee'], $info['total_fee']);
            if ($refund !== false || !empty($info['out_refund_no'])) {
                $data = array();
                $data['id'] = $info['id'];
                $data['out_refund_no'] = $out_refund_no;
                $data['status'] = '4';
                empty($info['cancel_date']) && $data['cancel_date'] = get_now_date();
                $result = $this->save($data);
            }
        }
        return ($result !== false);
    }

}
