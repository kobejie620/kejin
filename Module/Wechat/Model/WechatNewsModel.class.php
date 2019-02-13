<?php

namespace Wechat\Model;

use Think\Model;

class WechatNewsModel extends Model {

    /**
     * 自动完成
     * @var type 
     */
    protected $_auto = array(
        array('status', '2', self::MODEL_INSERT),
        array('create_by', 'get_user_id', self::MODEL_INSERT, 'function'),
        array('update_by', 'get_user_id', self::MODEL_BOTH, 'function'),
        array('update_date', 'get_now_date', self::MODEL_BOTH, 'function')
    );

}
