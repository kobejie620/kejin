<?php

namespace Apps\Model;

use Think\Model;

class AppsVoteModel extends Model {

    /**
     * 自动完成
     * @var type 
     */
    protected $_auto = array(
        array('create_by', 'get_user_id', self::MODEL_INSERT, 'function'),
        array('create_date', 'get_now_date', self::MODEL_BOTH, 'function'),
        array('update_by', 'get_user_id', self::MODEL_BOTH, 'function'),
        array('update_date', 'get_now_date', self::MODEL_BOTH, 'function')
    );

    public function getAll($where = array("status" => 2)) {
        return $this->where($where)->select();
    }

}
