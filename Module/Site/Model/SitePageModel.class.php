<?php

namespace Site\Model;

use Think\Model;

class SitePageModel extends Model {

    /**
     * 自动完成
     * @var type 
     */
    protected $_auto = array(
        array('status', '2', self::MODEL_INSERT),
        array('update_date', 'get_now_date', self::MODEL_BOTH, 'function'),
        array('create_by', 'get_user_id', self::MODEL_INSERT, 'function'),
        array('update_by', 'get_user_id', self::MODEL_BOTH, 'function'),
    );

    /**
     * 自动验证
     * @var type 
     */
    protected $_validate = array(
        array('code', '', '页面地址重复，请更换其它地址再试！', 0, 'unique', 1),
    );

}
