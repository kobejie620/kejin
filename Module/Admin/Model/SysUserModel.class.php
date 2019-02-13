<?php

namespace Admin\Model;

/**
 * 用户管理模型
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 13:51:45
 */
class SysUserModel extends AdminModel {

    /**
     * 自动验证
     * @var type 
     */
    protected $_validate = array(
        array('username', '', '用户帐号已经存在,请尝试更换用户名！', 0, 'unique', 1),
        array('password', 'require', '用户密码不能为空！', self::EXISTS_VALIDATE), //默认情况下用正则进行验证
    );

}
