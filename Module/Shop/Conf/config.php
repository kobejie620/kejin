<?php

/**
 * SHOP配置文件
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/11 13:52:58
 */
return array(
    /* 开发异常调试 */
    'SHOW_ERROR_MSG'  => false, // 显示错误信息
    'SHOW_PAGE_TRACE' => false, // 显示页面Trace信息

    /* 设置缓存前缀 */
    'COOKIE_PREFIX'  => 'wap', //Cookie 前缀
    'SESSION_PREFIX' => 'wap', //Session 前缀

    /* 强制URL模模式 */
    'URL_MODEL'       => 1, // URL访问模式
    /* 开启路由 */
    'URL_ROUTER_ON'   => true,
    // 路由规则
    'URL_ROUTE_RULES' => array(
        //支付路径 /shop/pay/type_id
        '/^pay\/(\w+)_(\d+)$/' => 'shop/:1/show?order_id=:2&credit_id=:2',
    ),
);
