<?php

namespace Library\Util;

/**
 * 字符串操作
 * 
 * @author zoujingli <zoujingli@qq.com>
 */
class Filter {

    /**
     * 去除左端指定的字符串
     * 
     * @param string $string
     * @param string $trim_str
     * @param string $chatset
     * @return string
     */
    static public function strLtrim($string, $trim_str, $chatset = 'UTF-8') {
        if (is_array($trim_str) || $trim_str = array($trim_str)) :
            foreach ($trim_str as $trim) :
                $trimLen = mb_strlen($trim, $chatset);
                $strLen = mb_strlen($string, $chatset);
                if (mb_substr($string, 0, $trimLen, $chatset) === $trim) :
                    $string = mb_substr($string, $trimLen, $strLen, $chatset);
                endif;
            endforeach;
        endif;
        return $string;
    }

    /**
     * 去除右端指定的字符串
     * 
     * @param string $string
     * @param string $trim_str
     * @param string $chatset
     * @return string
     */
    static public function strRtrim($string, $trim_str, $chatset = 'UTF-8') {
        if (is_array($trim_str) || $trim_str = array($trim_str)) :
            foreach ($trim_str as $trim) :
                $trimLen = mb_strlen($trim, $chatset);
                $strLen = mb_strlen($string, $chatset);
                if (mb_substr($string, $strLen - $trimLen, $trimLen, $chatset) === $trim) :
                    $string = mb_substr($string, 0, $strLen - $trimLen, $chatset);
                endif;
            endforeach;
        endif;
        return $string;
    }

    /**
     * 获取参数化的URL地址
     * @param array $params 需要替换的URL参数
     * @param str $func
     * @return string
     */
    static public function filterURL($params = array(), $func = "U") {
        $params = array_merge($_GET, $params);
        unset($params['_URL_']);
        foreach ($params as $key => $vo) {
            if (is_null($vo) || $vo === false) {
                unset($params[$key]);
            }
        }
        return $func(MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME, $params);
    }

    /**
     * 域名转换
     * 
     * @param type $url
     * @return type
     */
    static public function toDomain($url) {
        if (strpos($url, '/Static/Uploads/') !== false) :
            $url = strstr($url, '/Static/Uploads/');
        endif;
        if (stripos($url, 'http') === 0) :
            return $url;
        endif;
        $domain = get_domain();
        return $domain . str_replace('//', '/', __ROOT__ . '/' . str_ltrim($url, __ROOT__));
    }

    /**
     * 一维数据数组生成数据树
     * @param type $list 数据列表
     * @param type $id 父ID Key
     * @param type $pid ID Key
     * @param type $son 定义子数据Key
     * @return type
     */
    static public function getArrayTree($list, $id = 'id', $pid = 'pid', $son = 'sub') {
        $tree = array(); //格式化的树
        $tmpMap = array();  //临时扁平数据
        foreach ($list as $item) {
            $tmpMap[$item[$id]] = $item;
        }
        foreach ($list as $item) {
            isset($item[$pid]) &&
                    (isset($tmpMap[$item[$pid]]) ?
                            $tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]] :
                            $tree[] = &$tmpMap[$item[$id]]);
        }
        unset($tmpMap);
        return $tree;
    }

    /**
     * 一维数据数组生成数据树
     * @param type $list 数据列表
     * @param type $id ID Key
     * @param type $pid 父ID Key
     * @param type $path
     * @return type
     */
    static public function getSelectTree($list, $id = 'id', $pid = 'pid', $path = 'path', $ppath = '') {
        $_array_tree = self::getArrayTree($list);
        $tree = array(); //格式化的树
        foreach ($_array_tree as $_tree) {
            $_tree[$path] = $ppath . '-' . $_tree['id'];
            $count = substr_count($ppath, '-');
            $_tree['spl'] = str_repeat("&nbsp;&nbsp;&nbsp;├ ", $count);
            if (!isset($_tree['sub'])) {
                $_tree['sub'] = array();
            }
            $sub = $_tree['sub'];
            unset($_tree['sub']);
            $tree[] = $_tree;
            if (!empty($sub)) :
                $sub_array = self::getSelectTree($sub, $id, $pid, $path, $_tree[$path]);
                $tree = array_merge($tree, (Array) $sub_array);
            endif;
        }
        return $tree;
    }

    /**
     * 时间格式转换
     * @param type $time
     * @param string $format
     * @return string
     */
    static public function toDate($time = false, $format = null) {
        empty($format) && $format = 'Y-m-d H:i:s';
        if (empty($time)) {
            return '';
        }
        return date($format, $time);
    }

    /**
     * 友好时间显示
     * @param type $sTime
     * @param type $type
     * @return string
     */
    static public function friendlyDate($sTime, $type = 'normal') {
        if (empty($sTime)) {
            return '';
        }
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

}
