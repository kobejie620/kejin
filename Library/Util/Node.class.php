<?php

namespace Library\Util;

/**
 * 获取指定模块下控制器名及方法名
 * 
 * @version 2.0
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/12/15 11:18
 */
class Node {

    /**
     * 获取节点数组树
     * 
     * @param type $modules
     * @return boolean
     */
    static public function getNodeArrayTree($modules, $type = 'Controller') {
        $key = 'nodedata_' . md5(md5($modules) . '_' . $type);
        if (!APP_DEBUG && S($key)) {
            return S($key);
        }
        is_string($modules) && $modules = explode(',', $modules);
        if (empty($modules)) {
            return false;
        }

        $data = array();
        foreach ($modules as $module) {
            $classNameArray = self::getClassNameList($module, $type);
            $data[$module] = array();
            foreach ($classNameArray as $baseName => $className) {
                $methodNameArray = self::getMethodList($className);
                if (empty($methodNameArray)) {
                    continue;
                }
                $data[$module][$baseName] = array();
                foreach ($methodNameArray as $actionArr) {
                    if (strtoupper($baseName) === strtoupper($module)) {
                        unset($data[$module][$baseName]);
                        break;
                    } else {
                        $keys = array_keys($actionArr);
                        if (is_array($keys) && count($keys) > 0) {
                            $action = end($keys);
                            $data[$module][$baseName][$action] = $actionArr[$action];
                        }
                    }
                }
            }
            if (empty($data[$module])) {
                unset($data[$module]);
            }
        }
        S($key, $data);
        return $data;
    }

    /**
     * 获取所有控制器名称
     * 
     * @param type $module
     * @return null
     */
    static public function getClassNameList($module, $type = 'Controller') {
        if (empty($module)) {
            return null;
        }
        $module_path = APP_PATH . '/' . $module . '/' . $type . '/';
        if (!is_dir($module_path)) {
            return null;
        }
        $module_path .= '/*.class.php';
        $ary_files = glob($module_path);
        foreach ($ary_files as $file) {
            if (is_dir($file)) {
                continue;
            } else {
                $baseName = basename($file, $type . '.class.php');
                $className[$baseName] = '\\' . $module . '\\' . $type . '\\' . $baseName . $type;
            }
        }
        return $className;
    }

    /**
     * 获取所有Public方法名称
     * 
     * @param type $className
     * @return null
     */
    static public function getMethodList($className) {
        if (empty($className)) {
            return null;
        }

        //获取全部方法，包括被继承的方法
        $functions = get_class_methods($className);

        //控制器变量名称
        $vars = get_class_vars($className);

        //分组名
        $gtitle = isset($vars['gtitle']) ? $vars['gtitle'] : '';
        //控制器名称
        $ptitle = isset($vars['ptitle']) ? $vars['ptitle'] : '';

        //排除部分方法
        $inherents_functions = array('_initialize', '__construct', 'getActionName', 'isAjax', 'display', 'show', 'fetch', 'buildHtml', 'assign', '__set', 'get', '__get', '__isset', '__call', 'error', 'success', 'ajaxReturn', 'redirect', '__destruct', '_empty');

        //排除以指定前缀开头的方法
        $pre_inherents_functions = array('_before_', '_after_');
        foreach ($functions as $func) {
            $func = trim($func);
            foreach ($pre_inherents_functions as $pre) {
                if (stripos($func, $pre) === 0) {
                    continue 2;
                }
            }
            if (in_array($func, $inherents_functions)) {
                continue;
            }
            //设置方法属性
            if (!isset($vars['menu'])) {
                $vars['menu'] = $vars['access'];
            }
            $menu = array('status' => 0, 'title' => '');
            if (isset($vars['menu']) && isset($vars['menu'][$func])) {
                $menu['status'] = 1;
                $menu['title'] = $vars['menu'][$func];
            }

            if (isset($vars['access']) && isset($vars['access'][$func])) {
                $customer_functions[] = array($func => array('gtitle' => $gtitle, 'ptitle' => $ptitle, 'title' => $vars['access'][$func], 'menu' => $menu));
            } elseif (!isset($vars['access'])) {
                continue;
//                $customer_functions[] = array($func => array('gtitle' => $gtitle, 'ptitle' => $ptitle, 'title' => $func, 'menu' => $menu));
            }
        }
        return $customer_functions;
    }

}
