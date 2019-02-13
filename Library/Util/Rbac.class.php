<?php

namespace Library\Util;

/**
 * RBAC 基于角色的权限管理器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/07/26 16:11
 */
class Rbac {

    static protected $accessList = array();
    static protected $user = null;
    static protected $role = null;

    /**
     * 检测登录
     * 
     * @param type $username
     * @param type $password
     * @return boolean
     */
    static public function checkLogin($username, $password) {
        $map = array();
        $map['username'] = $username;
        $map['password'] = md5($password);
        self::$user = M('SysUser')->where($map)->find();

        // 用户信息不存在
        if (empty(self::$user)) {
            return false;
        }

        /**
         * 如果是系统用户
         * 状态必需为2才能登录
         */
        // if (self::$user['group'] === 'SYSTEM_GROUP' && self::$user['status'] < 2) {
        if (self::$user['status'] < 2) {
            return false;
        }

        //读取角色信息
        self::$role = M('SysRole')->find(self::$user['role']);

        //更新用户登录统计信息
        $data = self::$user;
        $data['login_date'] = get_now_date();
        $data['login_num'] ++;
        $data['login_ip'] = get_client_ip();
        M('SysUser')->save($data);

        session('user', self::$user);
        session('role', self::$role);
        return true;
    }

    /**
     * 检测Rule是否有访问权限
     * 
     * @param type $rule
     * @param type $role_id
     * @return boolean
     */
    static public function checkRule($rule = null, $role_id = null) {
        if ($rule === 'null') {
            return true;
        }
        if (session('user.username') === 'admin') {
            return true;
        }
        $rule = self::_filter($rule);
        /*  系统首页不需要认证 */
        if ($rule === 'ADMIN/INDEX/INDEX') {
            return true;
        }

        /* 节点授权数据 */
        self::_checkData($role_id);
        foreach (self::$accessList as $access) {
            if ($access === $rule) {
                return true;
            }
            if (stripos($access, $rule . '/') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取用户具体的访问Rule
     * 
     * @return type
     */
    static public function getAccessList() {
        self::_checkData();
        return self::$accessList;
    }

    /**
     * 对节点进行过滤处理
     * 
     * @param type $rule
     * @return type
     */
    static private function _filter($rule = null) {
        is_null($rule) && $rule = MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME;
        $arr = explode('/', trim($rule, '/ '));
        switch (count($arr)) {
            case 1:
            case 2:
            case 3:
                break;
            default :
                $rule = array_shift($arr) . '/' . array_shift($arr) . '/' . array_shift($arr);
        }
        return strtoupper($rule);
    }

    /**
     * 初始化用户权限Rule
     * 
     * @param type $role_id
     * @param type $replace
     */
    static private function _checkData($role_id = null, $replace = false) {
        if ($replace || empty(self::$accessList)) {
            is_null($role_id) && $role_id = session('user.role');
            self::$accessList = M('SysRoleNode')->where(array('role_id' => $role_id))->getField('node', true);
        }
    }

}
