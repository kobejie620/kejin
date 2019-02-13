<?php

namespace Ad\Model;

use Think\Model;
/**
 * 角色管理模型
 *
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 13:51:17
 */
class AdModel extends Model {

    /**
     * 自动验证
     * @var type
     */
    protected $_validate = array(
        array('ad_name', 'require', '广告名称不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('end_time', 'require', '截止时间不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('access_price', 'require', '接入单价不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('launch_price', 'require', '投放单价不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('ad_track_link','require','广告追踪链接不能为空!',self::EXISTS_VALIDATE), //默认情况下用正则进行验证
    );

    /**
     * 自动完成
     * @var type
     */
    protected $_auto = array(
        array('click_num', '0', self::MODEL_INSERT),
        array('similarity_click_num', '0', self::MODEL_INSERT),
        array('conversion_num', '0', self::MODEL_INSERT),
        array('ad_status', '0', self::MODEL_INSERT),
        array('add_time', 'get_now_date', self::MODEL_BOTH, 'function'),
    );
}
