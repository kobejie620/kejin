<?php

/**
 * 接口路由配置文件
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 13:52:58
 */
return array(
    // 关闭页面输出
    'SHOW_PAGE_TRACE' => false,
    'SHOW_ERROR_MSG'  => false,
    // 开启路由
    'URL_ROUTER_ON'   => true,
    // 路由规则
    'URL_ROUTE_RULES' => array(
        ':c/:a'   => 'api/:1/:2',
        ':api_id' => 'api/index/index',
    ),
);
