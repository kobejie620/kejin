<?php

namespace Ad\Model;

use Think\Model;

/**
 * 角色管理模型
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 13:51:17
 */
class PostBackModel extends Model {

    /**
     * 自动验证
     * @var type
     */
    protected $_validate = array(
        array('code', 'require', 'Code不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('ad_id', 'require', '广告不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('channel_id', 'require', '渠道不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
    );

    /**
     * 自动完成
     * @var type
     */
    protected $_auto = array(
        array('status', '2', self::MODEL_INSERT),
        array('add_time', 'get_now_time', self::MODEL_BOTH, 'function'),
    );
}
