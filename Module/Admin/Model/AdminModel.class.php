<?php

namespace Admin\Model;

use Think\Model;

/**
 * 后台管理基础模型
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/03 12:11:22
 */
class AdminModel extends Model {

    /**
     * 自动完成
     * @var type 
     */
    protected $_auto = array(
        array('create_by', 'get_user_id', self::MODEL_INSERT, 'function'),
        array('update_by', 'get_user_id', self::MODEL_BOTH, 'function'),
        array('update_date', 'get_now_date', self::MODEL_BOTH, 'function')
    );

}
