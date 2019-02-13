<?php

/* 开启调试模式 */
define("APP_DEBUG", true);

/* 定义应该根目录 */
define('APP_ROOT', str_replace('\\', '/', getcwd()) . '/');

/* 项目路径，不可更改 */
define('APP_PATH', APP_ROOT . 'Module/');

/* 定义基础核心组件路径 */
define('COMMON_PATH', APP_ROOT . 'Library/');

/* 安装检测 */
if (!file_exists(COMMON_PATH . 'Conf/db.php')) {
    $url = str_replace('//', '/', $_SERVER['HTTP_HOST'] . '/' . dirname($_SERVER['SCRIPT_NAME']) . '/Static/install');
    header("Location: http://{$url}");
    die();
}

/* 定义第三方插件目录 */
define('VENDOR_PATH', COMMON_PATH . 'Vendor/');

/* 文件上传目录 */
define('UPLOAD_PATH', APP_ROOT . 'Static/Uploads/');

/* 定义缓存路径 */
define("RUNTIME_PATH", APP_ROOT . 'Static/Runtime/');

/* Nginx PHPINFO BUG */
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

/* SESSION 会控制 */
session_name(md5(__FILE__));

//载入框架核心文件
require COMMON_PATH . 'Think/ThinkPHP.php';
