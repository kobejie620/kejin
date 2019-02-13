<?php

namespace Wechat\Model;

use Think\Model;
use Library\Util\Api\WechatPay;

class WechatPayModel extends Model {

    /**
     * 通用微信发红包接口
     * @param type $type   红包活动类型
     * @param type $openid    领取人openid
     * @param type $send_name    发送人名称
     * @param type $total_amount    发送金额单位（分）
     * @param type $wishing    红包祝福语
     * @param type $act_name    红包活动名称
     * @param type $remark    备注
     * @return type array
     */
    public static function grantRedPocket($openid, $send_name, $total_amount, $wishing, $act_name, $remark, $type, $type_id) {
        $wechat_config = M('WechatConfig')->where(array('id' => 1, 'status' => 2))->find();
        empty($wechat_config['partnerid']) && $this->error('请先配置公众号支付信息！');
        $wechatPay = new WechatPay($wechat_config);
        $mch_billno = $wechat_config['partnerid'] . date('Ymd', time()) . $this->random(10);    //商户订单号（每个订单号必须唯一） 组成： mch_id+yyyymmdd+10位一天内不能重复的数字。 
        $response = $wechatPay->sendRedPack($openid, $mch_billno, $send_name, $total_amount * 100, $wishing, $act_name, $remark); //通过微信接口发放红包
        if (($response['return_code'] == 'SUCCESS') && ($response['result_code'] == 'SUCCESS')) {
            $data = array();
            $red_pocket = array();
            $red_pocket['type_id'] = $type_id;                                                 //红包所模块类型id
            $red_pocket['type'] = $type;                                                       //红包所模块类型
            $red_pocket['mch_billno'] = $mch_billno;                                           //商户订单号 
            $red_pocket['total_amount'] = $total_amount * 100;                                 //付款金额，单位分 
            $red_pocket['wishing'] = $wishing;                                                 //红包祝福语 
            $red_pocket['remark'] = $remark;                                                   //备注信息 
            $red_pocket['openid'] = $openid;                                                   //领取人openid
            $red_pocket['send_type'] = 'API';                                                  //发放类型
            $red_pocket['act_name'] = $act_name;                                               //红包活动名称
            $red_pocket['hb_type'] = 'NORMAL';                                                 //红包类型，GROUP:裂变红包，NORMAL:普通红包
            $red_pocket['send_listid'] = $response['send_listid'];                             //红包订单的微信单号
            $red_pocket['send_time'] = date('Y-m-d H;i:s', strtotime($response['send_time'])); //红包发送时间
            $red_pocket['status'] = 'SENDING';                                                 //红包发放状态 
            M('WechatRedPacket')->add($red_pocket);
            $data['status'] = 0;
            $data['info'] = $response['return_msg'];
        } else {
            $data['status'] = 1;
            $data['info'] = $response['return_msg'];
        }
        return $data;
    }

    private function random($length, $chars = '0123456789') {
        $hash = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     * 刷新红包列表，获取最新红包领取数据
     */
    public function rushRedPacket() {
        $red_packet_model = M('WechatRedPacket');
        $red_packet = $red_packet_model->where(array('status' => array('in', 'SENT,SENDING')))->select();
        if (!empty($red_packet)) {
            $wechat_config = M('WechatConfig')->where(array('id' => 1, 'status' => 2))->find();
            empty($wechat_config['partnerid']) && $this->error('请先配置公众号支付信息！');
            $wechatPay = new WechatPay($wechat_config);
            foreach ($red_packet as $key => $value) {
                $hbInfo = $wechatPay->getHbInfo($value['mch_billno']);
                $red_packet_model->where(array('mch_billno' => $hbInfo['mch_billno']))->save(array('detail_id' => $hbInfo['detail_id'], 'status' => $hbInfo['status'], 'reason' => $hbInfo['reason'], 'refund_time' => $hbInfo['refund_time']));
            }
        }
    }

}
