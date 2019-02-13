<?php

namespace Channel\Model;

use Think\Model;

/**
 * 角色管理模型
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 13:51:17
 */
class ChannelModel extends Model {

    /**
     * 自动验证
     * @var type
     */
    protected $_validate = array(
        array('name', 'require', '广告主用户名！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('name', '', '广告主用户名已经存在,请尝试更换广告主用户名！', 0, 'unique', 1),
        array('email', 'require', '广告主邮箱不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('portrait', 'require', '广告主头像不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('identity', 'require', '身份不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('type', 'require', '类型不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('contacts_name', 'require', '联系人名字不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('contacts_emali', 'require', '联系人邮箱不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
//        array('contacts_emali', '', '联系人邮箱已经存在,请尝试更换联系人邮箱！', 0, 'unique', 1),
        array('contacts_mobile', 'require', '联系人手机号码不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
//        array('contacts_mobile', '', '联系人手机号码已经存在,请尝试更换联系人手机号码！', 0, 'unique', 1),
        array('contacts_qq', 'require', '联系人QQ不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
//        array('contacts_qq', '', '联系人QQ已经存在,请尝试更换联系人QQ！', 0, 'unique', 1),
        array('contacts_wechat', 'require', '联系人微信不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
//        array('contacts_wechat', '', '联系人微信已经存在,请尝试更换联系人微信！', 0, 'unique', 1),
    );

    /**
     * 自动完成
     * @var type
     */
    protected $_auto = array(
        array('ad_num', '0', self::MODEL_INSERT),
        array('channel_status', '0', self::MODEL_INSERT),
        array('add_time', 'get_now_date', self::MODEL_BOTH, 'function'),
    );
}
