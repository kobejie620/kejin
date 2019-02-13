<?php

namespace Wechat\Model;

use Library\Util\Api\Wechat;
use Think\Model;

/**
 * 微信卡券管理模型
 * 
 * @author yangjia <yj.joy@qq.com>
 * @date 2014/09/04 14:55:02
 */
class WechatCardModel extends Model {

    /**
     * 微信公众号配置信息
     * @需要调用需要调用getInstanceWechat方法
     * @var array 
     */
    protected $wechat_config = null;

    /**
     * 微信公众号操作对象
     * @需要调用getInstanceWechat方法
     * @var Wechet 
     */
    protected $wechat = null;

    /**
     * 自动完成
     * @var type 
     */
    protected $_auto = array(
        array('status', '2', self::MODEL_INSERT),
        array('create_by', 'get_user_id', self::MODEL_INSERT, 'function'),
        array('update_by', 'get_user_id', self::MODEL_BOTH, 'function'),
        array('update_date', 'get_now_date', self::MODEL_BOTH, 'function'),
    );

    /**
     * 核销卡券
     * 自定义 code（use_custom_code 为 true）的优惠券，在 code 被核销时，必须调用此接口。
     * @param type $code 要消耗的序列号
     * @param type $card_id 要消耗序列号所述的 card_id，创建卡券时use_custom_code 填写 true 时必填。
     * @return type array
     */
    public function consumeCardCode($code, $card_id = '') {
        $wechat = $this->getInstanceWechat();    //获取微信SDK操作对象
        $response = (empty($card_id)) ? $wechat->consumeCardCode($code) : $wechat->consumeCardCode($code, $card_id);
        $data = array();
        if ($response['errcode'] == 0) {
            $data['status'] = 0;
            $data['info'] = '卡券核销成功';
            $data['data'] = $response;
        } else {
            $data['status'] = 1;
            $data['info'] = $response['errmsg'];
        }
        return $data;
    }

    /**
     * 同步卡券列表
     */
    public function syncList() {
        $sum = 0;
        $wechat = $this->getInstanceWechat();    //获取微信SDK操作对象
        $color = $wechat->getCardColors();       //获取卡券颜色
        $query_list = $wechat->getCardIdList();  //获取卡券列表
        $query_count = ceil($query_list['total_num'] / 50);  //循环查询次数
        for ($i = 0; $i < $query_count; $i++) {
            $list = $wechat->getCardIdList($i * 50);
            foreach ($list['card_id_list'] as $value) {
                $card_info = $wechat->getCardInfoByCardId($value);
                $data = array();
                $card_type = $card_info['card']['card_type'];
                $info = $card_info['card'][strtolower($card_type)]['base_info'];
                $data['card_type'] = $card_type;
                /* 根据卡券类型添加专属字段 todo */
                $data['card_id'] = $info['id'];
                $data['quantity'] = $info['sku']['quantity'];
                $data['logo_url'] = $info['logo_url'];
                $data['code_type'] = $info['code_type'];
                $data['brand_name'] = $info['brand_name'];
                $data['title'] = $info['title'];
                $data['sub_title'] = $info['sub_title'];
                $data['date_info'] = json_encode($info['date_info']);
                foreach ($color['colors'] as $v) {
                    ($v['value'] == $info['color']) && $data['color'] = json_encode(array($v['name'] => $v['value']));
                }
                $data['notice'] = $info['notice'];
                $data['description'] = $info['description'];
                $data['location_id_list'] = empty($info['location_id_list']) ? 0 : implode(',', $info['location_id_list']);
                if (!empty($info['location_id_list'])) {
                    $store = array();
                    foreach ($info['location_id_list'] as $v) {
                        $store_info = $wechat->getpoi($v);
                        $store[] = $store_info['business']['base_info']['business_name'];
                    }
                    $data['location_name_list'] = implode(',', $store);
                } else {
                    $data['location_name_list'] = '所有门店';
                }
                $data['get_limit'] = $info['get_limit'];
                $data['can_share'] = $info['can_share'];
                $data['can_give_friend'] = $info['can_give_friend'];
                $data['examine_status'] = $info['status'];
                $data['default_detail'] = $info['default_detail'];
                $data['service_phone'] = $info['service_phone'];
                $data['custom_url_name'] = $info['custom_url_name'];
                $data['custom_url_sub_title'] = $info['custom_url_sub_title'];
                $data['custom_url'] = $info['custom_url'];
                $data['promotion_url_name'] = $info['promotion_url_name'];
                $data['promotion_url_sub_title'] = $info['promotion_url_sub_title'];
                $data['promotion_url'] = $info['promotion_url'];
                $check = M('CardCoupons')->where(array('card_id' => $info['id']))->find();
                if (empty($check)) {
                    $data['examine_status_time'] = date('Y-m-d H:i;s', time());
                    $res = M('CardCoupons')->data($data)->add();
                } else {
                    $res = M('CardCoupons')->where(array('card_id' => $info['id']))->save($data);
                }
                if (!empty($res)) {
                    $sum ++;
                }
            }
        }
        $syncListInfo = '共同步' . $sum . '个卡券信息';
        return $syncListInfo;
    }

    /**
     * 微信前台js添加到卡包的json配置
     * 指定领取者的openid，只有该用户能领取。bind_openid字段为true的卡券必须填写，bind_openid字段为false不必填写。
     * @param type $cardid 卡券card_id
     * @param type $openid 指定领取人openid
     * @return type array
     */
    public function bacthAddCard($cardid, $openid = '') {
        $wechat = $this->getInstanceWechat();          //获取微信SDK操作对象
        $cardApiTicket = $wechat->getCardApiTicket();  //获取CardApiTicket
        $timestamp = time();
        if (empty($openid)) {
            $tmpArr = array($timestamp, $cardApiTicket, $cardid);
            sort($tmpArr, SORT_STRING);
            $signature = sha1(implode($tmpArr));
            $cardInfo = array(
                "card_id" => $cardid,
                'card_ext' => json_encode(array(
                    'timestamp' => time(),
                    'signature' => $signature,
                )),
                'sign' => $signature,
            );
        } else {
            $tmpArr = array($timestamp, $cardApiTicket, $cardid, $openid);
            sort($tmpArr, SORT_STRING);
            $signature = sha1(implode($tmpArr));
            $cardInfo = array(
                "card_id" => $cardid,
                'card_ext' => json_encode(array(
                    'timestamp' => time(),
                    'signature' => $signature,
                    'openid' => $openid
                )),
                'sign' => $signature,
            );
        }
        return $cardInfo;
    }

    /**
     * 微信前台js选择卡卷
     * @param type $cardType  卡券类型，如果用户可选择的卡券类型，如果要显示全部卡券，可不填
     * @param type $cardId    卡券ID，用户只能选取某一种卡券时必填
     * @param type $shopId    门店卡券，只显示这个门店的卡券
     * @return type array
     */
    public function chooseCard($cardType = '', $cardId = '', $shopId = '') {
        $wechat = $this->getInstanceWechat();         //获取微信SDK操作对象
        $cardApiTicket = $wechat->getCardApiTicket(); //获取CardApiTicket
        $appid = $this->wechat_config['appid'];
        $timestamp = time();
        $nonce_str = md5(uniqid() . mt_rand(0, 1000));
        $tmpArr = array_filter(array($cardApiTicket, $appid, $timestamp, $nonce_str, $shopId, $cardType, $cardId));
        sort($tmpArr, SORT_STRING);
        $signature = sha1(implode($tmpArr));
        $cardInfo = array(
            "shopId" => $shopId,
            'cardType' => $cardType,
            'cardId' => $cardId,
            'timestamp' => $timestamp,
            'nonceStr' => $nonce_str,
            'signType' => 'SHA1',
            'cardSign' => $signature
        );
        return $cardInfo;
    }

    /**
     * 获取微信SDK操作对象
     * @return Wechat
     */
    protected function getInstanceWechat() {
        if (is_null($this->wechat)) {
            empty($this->wechat_config) && $this->_applyWechatConfig();
            $this->wechat = new Wechat($this->wechat_config);
        }
        return $this->wechat;
    }

    /**
     * 读取微信配置信息
     */
    private function _applyWechatConfig() {
        $map = array();
        $map['id'] = '1';
        $map['status'] = '2';
        $this->wechat_config = M('WechatConfig')->where($map)->find();
        empty($this->wechat_config) && R('Wap/Error/show', '公众号参数未配置', '请到系统后台进行操作!');
    }

}
