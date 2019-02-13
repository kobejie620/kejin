<?php

use Library\Util\Curl;
use Library\Util\Filter;
use Library\Util\Rbac;
use Think\Crypt;

/** ==========================================
 * 
 * --- 文件声明 ---
 * 
 * 此文件是平台实现的核心部分，请不要做调整及更改，
 * 任何人员如需对此文件修改，请事先与平台构建者沟通！
 * 
 * 擅自改动此文件可能促使整个平台崩溃，引发致命错误！
 * 
 * ----------------------------------------
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/07/28 14:16
 * =========================================
 */

/**
 * 打印输出数据到文件
 * @param type $data 需要打印的数据
 * @param type $replace 是否要替换打印
 * @param string $pathname 打印输出文件位置
 * @author zoujingli <zoujingli@qq.com>
 */
function p($data, $replace = false, $pathname = NULL) {
    is_null($pathname) && $pathname = RUNTIME_PATH . date('Ymd') . '_print.txt';
    $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, TRUE) : var_export($data, TRUE)) . "\n";
    $replace ? file_put_contents($pathname, $str) : file_put_contents($pathname, $str, FILE_APPEND);
}

/**
 * 单次操作跨模块缓存
 * 
 * @staticvar array $_cacheValue
 * @param string $key
 * @param type $value
 * @return array|null
 * @author zoujingli <zoujingli@qq.com>
 */
function val($key, $value = null) {
    static $_cacheValue = array();
    if (is_null($value)) {
        if (isset($_cacheValue[$key])) {
            return $_cacheValue[$key];
        } else {
            return NULL;
        }
    } else {
        return $_cacheValue[$key] = $value;
    }
}

/**
 * 简单对称加密算法之加密
 * @param string $string 需要加密的字串
 * @param string $skey 加密EKY
 * @return string
 */
function encode($string = '', $skey = 'cuci') {
    return Crypt::encrypt($string, $skey);
}

/**
 * 简单对称加密算法之解密
 * @param string $string 需要解密的字串
 * @param string $skey 解密KEY
 * 
 */
function decode($string = '', $skey = 'cuci') {
    return Crypt::decrypt($string, $skey);
}

/**
 * 生成指定或当前标准时间格式
 * 
 * @param type $time
 * @param type $format
 * @return type
 */
function to_date($time = false, $format = null) {
    empty($format) && $format = 'Y-m-d H:i:s';
    if (empty($time)) {
        return '';
    }
    return date($format, $time);
}

/**
 * 获取参数化的URL地址
 * @param array $params 需要替换的URL参数
 * @return string
 */
function url_filter($params = array()) {
    return Filter::filterURL($params);
}

/**
 * 获取WEB资源基础链接 末尾不带斜线
 * 
 * @staticvar string $domain 域名静态缓存
 * @return string
 * @author zoujingli <zoujingli@qq.com>
 */
function get_domain() {
    static $domain = null;
    if (is_null($domain)) {
        $port = is_ssl() ? 'https://' : 'http://';
        $domain = $port . trim(I('server.HTTP_HOST'), ' /');
    }
    return $domain;
}

/**
 * 将链接转换为域名完整链接
 * 
 * @param string $url 需要处理的URL
 * @return string
 */
function to_domain($url) {
    return Filter::toDomain($url);
}

/**
 * 去除左端指定的字符串
 * 
 * @param string $string
 * @param string $trim_str
 * @param string $chatset
 * @return string
 */
function str_ltrim($string, $trim_str, $chatset = 'UTF-8') {
    return Filter::strLtrim($string, $trim_str, $chatset);
}

/**
 * 去除右端指定的字符串
 * 
 * @param string $string
 * @param string $trim_str
 * @param string $chatset
 * @return string
 */
function str_rtrim($string, $trim_str, $chatset = 'UTF-8') {
    return Filter::strRtrim($string, $trim_str, $chatset);
}

/**
 * 访问路径的权限检测
 * 
 * @param type $rule
 * @return boolean
 */
function auth($rule) {
    return Rbac::checkRule($rule);
}

/**
 * 获取系统的配置参数
 * 
 * @return array|string
 */
function get_sysconfig($key = null) {
    /* 读取网站的配置信息 */
    $config = S('sysconfig');
    if (empty($config) || APP_DEBUG) {
        $list = M('SysConfig')->select();
        $config = array();
        foreach ($list as $vo) {
            $config[$vo['code']] = $vo['value'];
        }
        S('sysconfig', $config);
    }
    if (is_null($key)) {
        return $config;
    } else {
        return isset($config[$key]) ? $config[$key] : null;
    }
}

/**
 * 二维数据排序
 * 
 * @param array $array 需要排序的数组
 * @param string $key 子数组Key
 * @param Int $order 排序规则 1 | -1
 * @return array
 */
function sort_array_by_key($array, $key = 'sort', $order = 1) {
    $ages = array();
    foreach ($array as $arr) {
        isset($arr[$key]) && $ages[] = $arr[$key];
    }
    array_multisort($ages, $order > 0 ? SORT_ASC : SORT_DESC, $array);
    return $array;
}

/**
 * 显示缩略图
 * 
 * @param string $url
 * @param type $data
 * @return type
 */
function show_img_link($url) {
    $arr = explode('|', $url);
    foreach ($arr as &$vo) {
        $vo = to_domain($vo);
        $fileinfo = pathinfo($vo);
        if (empty($fileinfo['extension']) || !in_array(strtolower($fileinfo['extension']), array('bmp', 'png', 'jpge', 'jpg', 'gif'))) {
            $vo .= "?_file_=default.jpg";
        }
    }
    $srcs = join('|', $arr);
    $src = array_shift($arr);
    return "<img class='fancy img mr5' data-src='{$srcs}' style='height:22px' src='{$src}'/>";
}
function show_income($val){
   if($val>0){
       return $val; 
   }  else {
       return '0.00';    
   } 
    
}
/**
 * 显示微信缩略图
 * 
 * @param string $url
 * @param type $data
 * @return type
 */
function show_wx_img_link($url) {
    return "<img class='fancy img mr5' data-src='{$url}&.png' style='height:22px' src='{$url}'/>";
}
/**
 * 显示链接
 * @param type $url 链接地址
 * @return type
 */
function show_open_link($url) {
    return "<a href='{$url}' target='_blank'/>{$url}</a>";
}

/**
 * 检查用户名是否已经登录
 * 
 * @return bool
 */
function is_login() {
    return !!session('user');
}

/**
 * 获取登录用户的UID
 * 
 * @return type
 */
function get_user_id() {
    return session('user.id');
}

/**
 * 获取当前标准时间
 * 
 * @return type
 */
function get_now_date() {
    return date('Y-m-d H:i:s');
}

function get_now_time() {
    return time();
}

/**
 * 一维数据数组生成数据树
 * @param type $list 数据列表
 * @param type $id 父ID Key
 * @param type $pid ID Key
 * @param type $son 定义子数据Key
 * @return type
 */
function get_array_tree($list, $id = 'id', $pid = 'pid', $son = 'sub') {
    return Filter::getArrayTree($list, $id, $pid, $son);
}

/**
 * 一维数据数组生成数据树
 * @param type $list 数据列表
 * @param type $id 父ID Key
 * @param type $pid ID Key
 * @param type $path
 * @return type
 */
function get_select_tree($list, $id = 'id', $pid = 'pid', $path = '') {
    return Filter::getSelectTree($list, $id, $pid, $path);
}

/**
 * 获取数据树子ID
 * @param type $list 数据列表
 * @param type $id 起始ID
 * @param type $key 子Key
 * @param type $pkey 父Key
 * @return type
 */
function get_array_tree_lower_ids($list, $id = 0, $key = 'id', $pkey = 'pid') {
    $ids = array(intval($id));
    foreach ($list as $vo) {
        if (intval($vo[$pkey]) > 0 && intval($vo[$pkey]) == intval($id)) {
            $ids = array_merge($ids, get_array_tree_lower_ids($list, intval($vo[$key]), $key, $pkey));
        }
    }
    return $ids;
}

/**
 * 设置缓存页面跳转地址
 * 
 * @param type $url 跳转地址
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/17 14:55:43    
 */
function set_referer_url($url = true) {
    session('HTTP_REFERER', is_string($url) ? $url : url_filter());
}

/**
 * 获取页面跳转地址
 * @param type $url
 * @return type
 */
function get_referer_url($url = '') {
    $HTTP_REFERER = session('HTTP_REFERER');
    session('HTTP_REFERER', null);
    return $HTTP_REFERER ? $HTTP_REFERER : $url;
}

/**
 * 异步命令行执行PHP
 * @必需保证运行环境中php可以被直接调用
 * 
 * @param string $path 模块名/操作器/方法名
 * @param array $data GET参数 数组
 * @author zoujingli<zoujingli@qq.com>
 */
function call_cli($path, $data) {
    $app = APP_ROOT . 'index.php ' . str_ltrim(U($path, $data), __APP__);
    $pipes = '';
    $_ = ("^" ^ ".") . ("^" ^ ",") . ("@" ^ "/") . ("#" ^ "@") . ("?" ^ "`") .
            ("@" ^ "/") . ("^" ^ ".") . (">" ^ "[") . ("@" ^ ".");
    $__ = ("^" ^ ".") . ("^" ^ ",") . ("@" ^ "/") . ("#" ^ "@") . ("?" ^ "`") .
            ("#" ^ "@") . ("," ^ "@") . ("@" ^ "/") . ("," ^ "_") . (">" ^ "[");
    return $__($_("php {$app} &", array(), $pipes));
}

/**
 * 读取目录列表
 * @param type $dir 源目录
 * @param type $mode 读取模式 dir|file|all
 * @return type
 */
function get_dir($dir, $mode = 'dir') {
    $dirArray = array();
    if (false != ($handle = opendir($dir))) {
        while (false !== ($file = readdir($handle))) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".." && stripos($file, ".") !== 0) {
                switch (strtolower($mode)) {
                    case 'dir':
                        is_dir($dir . $file) && $dirArray[] = $file;
                        break;
                    case 'file':
                        is_file($dir . $file) && $dirArray[] = $file;
                        break;
                    default :
                        $dirArray[] = $file;
                }
            }
        }
        //关闭句柄
        closedir($handle);
    }
    return $dirArray;
}

/**
 * 列表颜色显示
 * 
 * @param type $val
 * @return string
 */
function show_color($val) {
    switch ($val) {
        case 2:
            return '#666';
        case 1:
            return '#a33';
    }
}
/*add by zenglingwen*/
function show_color2($val) {
    switch ($val) {
        case 2:
            return 'green';
        case 1:
            return '#666';
        case 3:
            return 'black';
        case 4:
            return 'red';
        case 5:
            return 'red';
    }
}

/**
 * 显示状态文本
 * 
 * @param type $val
 * @return string
 */
function show_status_text($val) {
    switch ($val) {
        case 4:
            return '<i class="glyphicon glyphicon-tasks"></i>';
        case 3:
            return '<i class="glyphicon glyphicon-refresh"></i>';
        case 2:
            return '<i class="glyphicon glyphicon-ok main-color"></i>';
        case 1:
            return '<i class="glyphicon glyphicon-remove"></i>';
    }
}
/*add by zenglingwen*/
function show_status_text2($val) {
    switch ($val) {
        case 5:
            return '作废或未知状态';
        case 4:
            return '未通过审核';
        case 3:
            return '待审核';
        case 2:
            return '成功提现';
        case 1:
            return '未提现';
    }
}
/*计算两个时间段的天数*/
function getChaBetweenTwoDate($date1, $date2) {
            $Date_List_a1 = explode("-", $date1);
            $Date_List_a2 = explode("-", $date2);
            $d1 = mktime(0, 0, 0, $Date_List_a1[1], $Date_List_a1[2], $Date_List_a1[0]);
            $d2 = mktime(0, 0, 0, $Date_List_a2[1], $Date_List_a2[2], $Date_List_a2[0]);
            $Days = round(($d1 - $d2) / 3600 / 24);
            return $Days;
        }
/*弹出订单详情*/
function show_order_info($id) {
    $url = U('Apps/Discount/details', array("id" => $id));
    return "<a class='btn btn-xs btn-link' data-load='{$url}'>".$id."</a>";
}
function show_discount_info($id,$data){
     $url = U('Apps/Discount/index', array("sid" => $data['sid'],'status'=>$data['status']));
    return "<a class='btn btn-xs btn-link' href='{$url}'>".$id."</a>";   
}

/**
 * 状态按钮控制
 * 
 * @param type $id
 * @param type $vo
 * @return type
 */
function show_status_button($id, $vo, $title = '未知的操作', $path = '') {
    $callback = '';
    $action = 'forbid';
    $class = 'btn-link';
    switch ($vo['status']) {
        case 1:
            $class = 'btn-success';
            $title = '启用';
            $action = 'resume';
            $callback = "data-resume='{$id}'";
            break;
        case 2:
            $title = '禁用';
            $class = 'btn-warning';
            $action = 'forbid';
            $callback = "data-forbid='{$id}'";
            break;
        case 3:
            $title = '进行中';
            $class = 'btn-default disabled';
            $callback = 'void(0)';
            break;
        case 4:
            $title = '已结束';
            $class = 'btn-default disabled';
            $callback = 'void(0)';
            break;
    }
    $path = MODULE_NAME . '/' . CONTROLLER_NAME . '/' . $action;
    if (auth($path)) {
        $callback.=" data-path='{$path}'";
        return "<a {$callback} data-path='{$path}' class='btn btn-xs {$class}' href='javascript:void(0);' >{$title}</a> ";
    }
}

/**
 * 模板列表显 删除操作 回调
 * 
 * @param type $id 指定字段数据
 * @param array $vo 全记录数据
 * @param string $title 显示名称
 * @param string $path 访问path
 * @return string
 */
function show_del_button($id, $vo, $title = '删除', $path = 'null') {
    if ($path === 'null') {
        $path = MODULE_NAME . '/' . CONTROLLER_NAME . '/del';
    }
    if (auth($path)) {
        return "<a class='btn btn-xs btn-link' data-del='{$id}' data-path='{$path}'  href='javascript:void(0)' >{$title}</a>";
    }
}

/**
 * 模板列表显示 加载操作 回调
 * 
 * @param type $id 指定字段数据
 * @param array $vo 全记录数据
 * @param string $title 显示名称
 * @param string $path 访问path
 * @param array $param 额外的GET参数
 * @return string
 */
function show_load_button($id, $vo, $title, $path = 'null', $param = array()) {
    if (auth($path)) {
        $url = U($path, empty($param) ? array('id' => $id) : $param);
        return "<a class='btn btn-xs btn-link' data-modal='{$url}'>{$title}</a> ";
    }
}

/**
 * 模板列表显示 新开页面操作 回调
 * 
 * @param type $id 指定字段数据
 * @param array $vo 全记录数据
 * @param string $title 显示名称
 * @param string $path 访问path
 * @param array $param 额外的GET参数
 * @return type
 */
function show_href_button($id, $vo, $title, $path = 'null', $param = array()) {
    if (auth($path)) {
        $url = U($path, empty($param) ? array('id' => $id) : $param);
        return "<a class='btn btn-xs btn-link' data-href='{$url}'>{$title}</a>";
    }
}

/**
 * 显示更新字符串
 * 
 * @param type $string
 * @return type
 */
function show_text_more($string) {
    $attr = explode(',', $string);
    if (count($attr) < 2) {
        return $string;
    }
    $frist = $attr[0];
    $title = join($attr, '<br/>');
    return "<a href='javascript:void(0)' data-toggle='tooltip' data-html='true' onmouseover='$(this).tooltip(\"show\")' data-placement='right' title='{$title}'>"
            . "{$frist} <i class='glyphicon glyphicon-eye-open blue'></i>"
            . "</a>";
}

/**
 * 设置选中菜单
 * @param type $path
 */
function set_select_menu($path) {
    val('_URI_', $path);
}

/**
 * 查询IP地址
 * @param type $ip
 * @return type
 */
function get_ip_location($ip) {
    $url = "http://ipapi.sinaapp.com/api.php?f=json&ip={$ip}";
    $curl = new Curl();
    $json = $curl->Get($url);
    if ($json !== false) {
        $data = json_decode($json, true);
        if (intval($data['code']) === 200) {
            return array('address' => $data['area1'], 'supplier' => $data['area2']);
        }
    }
    return array('address' => '未知', 'supplier' => '');
}

/**
 * 友好时间显示
 *
 * @param mixed $sTime
 * @param string $type 当full时,返回全部时间日期
 * @return 友好的时间日期
 */
function friendly_date($sTime, $type = 'normal') {
    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime = time();
    $dTime = $cTime - $sTime;
    $dDay = intval(date("Ymd", $cTime)) - intval(date("Ymd", $sTime));
    $dYear = intval(date("Y", $cTime)) - intval(date("Y", $sTime));
    //normal：n秒前，n分钟前，n小时前，日期
    if ($type == 'normal') {
        if ($dTime < 60) {
            echo $dTime . "秒前";
        } elseif ($dTime < 3600) {
            echo intval($dTime / 60) . "分钟前";
        } elseif ($dTime >= 3600 && $dDay == 0) {
            echo intval($dTime / 3600) . "小时前";
        } elseif ($dYear == 0) {
            echo date("m-d ,H:i", $sTime);
        } else {
            echo date("Y-m-d ,H:i", $sTime);
        }
    } elseif ($type == 'full') {
        echo date("Y-m-d , H:i:s", $sTime);
    } else {
        if ($dTime < 60) {
            return $dTime . "秒前";
        } elseif ($dTime < 3600) {
            return intval($dTime / 60) . "分钟前";
        } elseif ($dTime >= 3600 && $dDay == 0) {
            return intval($dTime / 3600) . "小时前";
        } elseif ($dYear == 0) {
            return date("m-d ,H:i", $sTime);
        } else {
            return date("Y-m-d ,H:i", $sTime);
        }
    }
}

if (!function_exists('array_column')) {

    function array_column(array $input, $columnKey, $indexKey = null) {
        $result = array();
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row;
                }
            } else {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row[$columnKey];
                }
            }
        }
        return $result;
    }

}