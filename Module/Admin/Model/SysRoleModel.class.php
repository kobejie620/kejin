<?php

namespace Admin\Model;

/**
 * 角色管理模型
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 13:51:17
 */
class SysRoleModel extends AdminModel {

    /**
     * 自动验证
     * @var type 
     */
    protected $_validate = array(
        array('name', '', '角色名已经存在,请尝试更换角色名！', 0, 'unique', 1), 
    );

}
