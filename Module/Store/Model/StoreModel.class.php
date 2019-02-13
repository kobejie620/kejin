<?php

namespace Store\Model;

use Think\Model;

/**
 * 店铺管理基础模型
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 14:05:59
 */
class StoreModel extends Model {

    /**
     * 自动完成
     * @var type 
     */
    protected $_auto = array(
        array('status', '2', self::MODEL_INSERT),
//        array('api_id', 'getApiId', self::MODEL_BOTH, 'callback'),
        array('create_by', 'get_user_id', self::MODEL_INSERT, 'function'),
        array('create_date', 'get_now_date', self::MODEL_INSERT, 'function'),
        array('update_by', 'get_user_id', self::MODEL_BOTH, 'function'),
        array('update_date', 'get_now_date', self::MODEL_BOTH, 'function'),
    );

//    protected function getApiId() {
//        return session('api.id');
//    }

}
