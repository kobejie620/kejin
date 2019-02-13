<?php

namespace Site\Model;

use Think\Model;

class SiteContentModel extends Model {
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
        array('title', 'require', '标题不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
        array('content', 'require', '内容不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
    );

}
