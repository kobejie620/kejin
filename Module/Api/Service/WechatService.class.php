<?php

namespace Api\Service;

use Library\Controller\Controller;
use Library\Util\Api\Wechat;

/**
 * 微信接口具体实现操作
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2015/04/24 11:30
 */
class WechatService extends Controller {

    /**
     * 接口操作对象
     * @var type 
     */
    protected $wechat = null;

    /**
     * 接口配置信息
     * @var type 
     */
    protected $config = null;

    /**
     * 微信用户OPENID
     * @var type 
     */
    protected $openid = null;

    /**
     * 配置文件过滤
     * 
     * @param type $config
     * @return type
     */
    protected function _filterConfig($config) {
        return $this->config = array(
            'token' => $config['token'], //填写你设定的key
            'appid' => $config['appid'], //填写高级调用功能的app id
            'appsecret' => $config['appsecret'], //填写高级调用功能的密钥
            'partnerid' => $config['partnerid'], //财付通商户身份标识
            'partnerkey' => $config['partnerkey'], //财付通商户权限密钥Key
            'paysignkey' => $config['paysignkey'], //商户签名密钥Key
            'encodingaeskey' => $config['encodingaeskey'], //加密秘钥
            'reply' => $config['reply'], //关注时的自动回复消息
        );
    }

    /**
     * 微信接口初始化方法
     */
    public function __construct($config = array()) {
        parent::__construct();

        /* 创建接口操作对象 */
        $this->wechat = new Wechat($this->_filterConfig($config));

        /* 验证接口 */
        $this->wechat->valid();

        /**
         * 获取openid
         */
        $this->openid = $this->wechat->getRev()->getRevFrom();

        /* 记录接口日志 */
        $this->_logs();

        /* 分别执行对应类型的操作 */
        switch ($this->wechat->getRev()->getRevType()) {
            case Wechat::MSGTYPE_TEXT:
                $this->_text();
                break;
            case Wechat::MSGTYPE_EVENT:
                $this->_event();
                break;
            case Wechat::MSGTYPE_IMAGE:
                $this->_image();
                break;
            case Wechat::MSGTYPE_LOCATION:
                $this->_location();
                break;
            default:
                $this->_default();
        }
    }

    /**
     * 微信关键字处理
     * @param type $keys
     */
    protected function _keys($keys) {

        $data = array();

        /**
         * 获取关键字对应的内容
         */
        $map = array();
        $map['keys'] = trim($keys);
        $list = M('WechatKeys')->where($map)->select();

        /**
         * 根据关键字记录的类型做出应答
         * 1、文件消息
         * 2、图文消息
         * 3、默认消息 @todo 需要根据项目订制
         */
        foreach ($list as $vo) {
            switch ($vo['type']) {
                /* 文本消息 */
                case 'text':
                    if (empty($vo['url'])) {
                        $data['text'].="{$vo['content']}\n";
                    } else {
                        $data['text'].="<a href='{$vo['url']}'>{$vo['content']}</a>";
                    }
                    break;
                /* 图文消息 */
                case 'news':
                    $row = array(
                        'Title' => $vo['title'],
                        'Description' => str_replace(array(' ', '&nbsp;'), '', $vo['content']),
                    );
                    empty($vo['link']) or $row['PicUrl'] = to_domain($vo['link']);
                    empty($vo['url']) or $row['Url'] = to_domain($vo['url']);
                    $data['news'][] = $row;
                    break;
                /* 默认消息 */
                default :
            }
        }
        /**
         * 返回消息结果
         * 1、如果存在图文消息，优先回复图文消息
         * 2、如果不存在图文消息，回复文件类型消息
         * 3、消息转发给客服处理
         */
        if (!empty($data['news'])) {
            $this->wechat->news($data['news'])->reply();
        } elseif (!empty($data['text'])) {
            $this->wechat->text($data['text'])->reply();
        } else {
            $this->wechat->transfer_customer_service()->reply();
        }
    }

    /**
     * 文本类型消息
     * 1、直接作为关键字处理
     */
    protected function _text() {
        $this->_keys($this->wechat->getRevContent());
    }

    /**
     * 位置类事情回复
     */
    protected function _location() {
        $vo = $this->wechat->getRevData();
        $url = "http://apis.map.qq.com/ws/geocoder/v1/?location={$vo['Location_X']},{$vo['Location_Y']}&key=ZBHBZ-CHQ2G-RDXQF-I5TUX-SAK53-A5BZT";
        $data = json_decode(file_get_contents($url), true);
        if (!empty($data) && intval($data['status']) == 0) {
            $msg = $data['result']['formatted_addresses']['recommend'];
        } else {
            $msg = "{$vo['Location_X']},{$vo['Location_Y']}";
        }
        $this->wechat->text($msg)->reply();
    }

    /**
     * 事件处理
     */
    protected function _event() {
        $event = $this->wechat->getRevEvent();
        switch ($event['event']) {
            /**
             * 用户关注的行为事件处理
             * 1、创建用户记录
             * 2、判断是否有设置自动回复
             */
            case 'subscribe': //关注事件
                $user = $this->wechat->getUserInfo($this->openid);
                $eventKey = $this->wechat->getRevSceneId();
                if ($user === false) {
                    $user = array('openid' => $this->openid);
                    $user['create_date'] = date('Y-m-d H:i:s', $user['subscribe_time']);
                    $user['sex'] = '3';
                }
                $sex = array('1' => '男', '2' => '女', '3' => '保密');
                $user['status'] = '2';
                $user['sex'] = isset($sex["{$user['sex']}"]) ? $sex["{$user['sex']}"] : '未知';
                $user['update_date'] = get_now_date();
                $this->_save($user, M('WechatMember'), array('openid' => $this->openid));
                if (!empty($eventKey) && $this->openid) {

                    $is_sid = M('Member')->where(array('openid' => $this->openid))->find();
                    if (empty($is_sid['sid'])) {
                        $info = array('openid' => $this->openid, 'sid' => $eventKey);
                        $this->_save($info, M('Member'), array('openid' => $this->openid));
                    } else {
                        $info = array('openid' => $this->openid);
                        $this->_save($info, M('Member'), array('openid' => $this->openid));
                    }
                }
                if (!empty($this->config['reply'])) {
                    $this->wechat->text($this->config['reply'])->reply();
                }
                break;
            /**
             * 用户取消关注的行为事件处理
             * 1、更新微信会员的状态
             */
            case 'unsubscribe': //取消关注
                $user = array();
                $user['openid'] = $this->wechat->getRevFrom();
                $user['status'] = '1';
                $user['update_date'] = get_now_date();
                $this->_save($user, M('WechatMember'), array('openid' => $this->openid));
                break;
            /**
             * 微信菜单点击事件
             * 1、获取完整的菜单配置信息
             * 2、根据菜单的类型读取数据并应答
             */
            case 'CLICK':
                $id = str_ltrim($event['key'], 'mid');
                $menu = M('WechatMenu')->find($id);
                switch ($menu['type']) {
                    case 'text':
                        $this->wechat->text($menu['content'])->reply();
                        break;
                    case 'keys':
                    case 'image':
                        $this->_keys($menu['keys']);
                        break;
                }
                break;
            /**
             * 扫码推事件的事件推送
             */
            case 'scancode_push':
            case 'scancode_waitmsg':
                $scanInfo = $this->wechat->getRev()->getRevScanInfo();
                $this->_keys($scanInfo['ScanResult']);
                break;
            case 'SCAN':
                $keys = $this->wechat->getRevEvent();
                if (!empty($keys['key'])) {
                    $this->_keys($keys['key']);
                } else {
                    $this->wechat->transfer_customer_service()->reply();
                }
                break;
            /**
             * 微信卡券通过审核
             */
            case 'card_pass_check':
                $card_id = $this->wechat->getRevCardPass();
                $data = array();
                $data['examine_status'] = 'CARD_STATUS_VERIFY_OK';
                $data['examine_status_time'] = date('Y-m-d H:i:s', time());
                $this->_save($data, M('WechatCard'), array('card_id' => $card_id));
                break;
            /**
             * 卡券未通过审核
             */
            case 'card_not_pass_check':
                $card_id = $this->wechat->getRevCardPass();
                $data = array();
                $data['examine_status'] = 'CARD_STATUS_VERIFY_FAIL';
                $data['examine_status_time'] = date('Y-m-d H:i:s', time());
                $this->_save($data, M('WechatCard'), array('card_id' => $card_id));
                break;
            /**
             * 用户删除卡券
             */
            case 'user_del_card':
                $card_info = $this->wechat->getRevCardDel();
                $data = array();
                $data['examine_status'] = 'CARD_STATUS_DELETE';
                $data['status'] = 3;
                $data['examine_status_time'] = date('Y-m-d H:i:s', time());
                (!empty($card_info['UserCardCode'])) && $data['user_card_code'] = $card_info['UserCardCode'];
                $this->_save($data, M('WechatCard'), array('card_id' => $card_info['CardId']));
                break;
            /**
             * 卡券领取事件
             */
            case 'user_get_card':
                $card_info = $this->wechat->getRevCardGet();
                $data = array();
                $data['card_id'] = $card_info['CardId'];      //卡券的card_id
                $data['openid'] = $card_info['FromUserName']; //领取者的openid
                $data['is_give_by_friend'] = $card_info['IsGiveByFriend']; //是否为转赠，1 代表是，0 代表否。
                if ($card_info['IsGiveByFriend'] == 1) {
                    $data['friend_user_openid'] = $card_info['FriendUserName']; //赠送方账号（一个OpenID），"IsGiveByFriend”为1时填写该参数。 
                    $data['old_user_card_code'] = $card_info['OldUserCardCode']; //转赠前的code序列号。
                    $this->_save(array('status' => 1, 'consume_source' => 'DONATION'), M('WechatCardReceive'), array('user_card_code' => $data['old_user_card_code']));
                }
                $data['user_card_code'] = $card_info['UserCardCode'];   //code序列号。自定义code及非自定义code的卡券被领取后都支持事件推送。
                $data['create_time'] = $card_info['CreateTime'];        //消息创建时间 （整型）。
                $data['outer_id'] = $card_info['OuterId'];              //领取场景值，用于领取渠道数据统计。可在生成二维码接口及添加JS API接口中自定义该字段的整型值。
                $this->_save($data, M('WechatCardReceive'));            //保存领取数据
                M('WechatCard')->where(array('card_id' => $card_info['CardId']))->setDec('quantity'); //卡券库存-1 
                break;
            /**
             * 卡券核销
             */
            case 'user_consume_card':
                $card_info = $this->wechat->getRevCardConsume();
                $data = array();
                $data['consume_source'] = $card_info['ConsumeSource'];      //卡券核销来源
                $data['user_consume_card_time'] = $card_info['CreateTime']; //卡券核销时间
                $data['status'] = 1;                                        //将卡券状态置为1
                $this->_save($data, M('WechatCardReceive'), array('user_card_code' => $card_info['UserCardCode'], 'card_id' => $card_info['CardId']));            //保存领取数据
                break;
            default :
            //其它操作
        }
    }

    /**
     * 图片事件处理
     */
    protected function _image() {
        
    }

    /**
     * 默认事件处理
     */
    protected function _default() {
        $this->wechat->text("help info")->reply();
    }

    /**
     * 记录接口日志
     */
    protected function _logs() {
        $data = $this->wechat->getRev()->getRevData();
        $data['ReceivedTime'] = time();
        if (in_array($data['Event'], array('scancode_push', 'scancode_waitmsg'))) {
            $scanInfo = $this->wechat->getRev()->getRevScanInfo();
            $data = array_merge($data, $scanInfo);
        }
        if (in_array($data['Event'], array('location_select'))) {
            $locationInfo = $this->wechat->getRev()->getRevSendGeoInfo();
            $data = array_merge($data, $locationInfo);
        }
        $WechatMsgModel = M('WechatMsg');
        $WechatMsgModel->create(array_change_key_case($data, CASE_LOWER));
        $WechatMsgModel->add();
    }

}
