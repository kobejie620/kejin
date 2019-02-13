<?php

namespace Ad\Model;

use Think\Model;

/**
 * 后台管理基础模型
 *
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/03 12:11:22
 */
class AdTypeModel extends Model {

    /**
     * 自动完成
     * @var type
     */
    protected $_auto = array(
        array('type_name', 'require', '分类名称不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('add_time', 'get_now_time', self::MODEL_INSERT, 'function'),
    );

}
