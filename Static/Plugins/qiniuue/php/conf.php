<?php

/*
 * @description   文件上传方法
 * @author widuu  http://www.widuu.com
 * @mktime 08/01/2014
 */

global $QINIU_ACCESS_KEY;
global $QINIU_SECRET_KEY;

$QINIU_UP_HOST = 'http://up.qiniu.com';
$QINIU_RS_HOST = 'http://rs.qbox.me';
$QINIU_RSF_HOST = 'http://rsf.qbox.me';

/**
 * 判断是否SSL协议
 * @return boolean
 */
function is_ssl() {
    if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
        return true;
    } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
        return true;
    }
    return false;
}

define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? 1 : 0 );
// 当前文件名
if (IS_CGI) {
    //CGI/FASTCGI模式下
    $_temp = explode('.php', $_SERVER['PHP_SELF']);
    define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0] . '.php'), '/'));
} else {
    define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
}
$_root = rtrim(dirname(_PHP_FILE_), '/');
define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
$port = is_ssl() ? 'https://' : 'http://';
$domain = $port . trim($_SERVER['HTTP_HOST'], ' /');
$configUrl = dirname(dirname(dirname(dirname(__ROOT__)))) . '/index.php/Site/Upload/getconfig';
die(file_get_contents($domain . $configUrl));

//配置$QINIU_ACCESS_KEY和$QINIU_SECRET_KEY 为你自己的key
$QINIU_ACCESS_KEY = 'OAFHGzCgZjod2-s4xr-g5ptkXsNbxDO_t2fozIEC';
$QINIU_SECRET_KEY = 'gy0aYdSFMSayQ4kMkgUeEeJRLThVjLpUJoPFxd-Z';

//配置bucket为你的bucket
$BUCKET = "qnue";

//配置你的域名访问地址
$HOST = "http://7xie1g.com1.z0.glb.clouddn.com/";

