<?php

/**
 * 身份状态
 * @param type $type_id
 * @return type
 */
function get_identity_name($type_id = null) {
    $status = array(
        '1' => '公司',
        '2' => '个人',
    );
    return is_null($type_id) ? $status : $status[$type_id];
}

/**
 * 类型  1  广告主 2  渠道  3 广告平台 4 代理商 5 其他
 * @param type $type_id
 * @return type
 */
function get_type_name($type_id = null) {
    $status = array(
        '1' => '广告主',
        '2' => '渠道',
        '3' => '广告平台',
        '4' => '代理商',
        '5' => '其他',
    );
    return is_null($type_id) ? $status : $status[$type_id];
}

/**
 * 类型   1  直链广告  2 动态广告
 * @param type $type_id
 * @return type
 */
function get_adv_type_name($type_id = null) {
    $status = array(
        '1' => '直链广告',
        '2' => '动态广告',
    );
    return is_null($type_id) ? $status : $status[$type_id];
}


/**
 * 结算方式  1 CPA  2 CPI   3 CPS  4  CPC  5 CPM  6 Game Intermodal
 * @param type $type_id
 * @return type
 */
function get_settlement_type_name($type_id = null) {
    $status = array(
        '1' => 'CPA',
        '2' => 'CPI',
        '3' => 'CPS',
        '4' => 'CPC',
        '5' => 'CPM',
        '5' => 'Game Intermodal',
    );
    return is_null($type_id) ? $status : $status[$type_id];
}


/**
 * 获取广告分类信息
 * @param type $id
 * @return type
 */
function get_ad_type_name($id) {
    return M('AdType')->where(array('type_id' => $id))->getField('type_name');
}


/**
 * 获取广告主信息
 * @param $id
 * @return mixed
 */
function get_advertiser_name($id) {
    return M('Advertiser')->where(array('advertiser_id' => $id))->getField('name');
}

/**
 * 获取商务经理
 * @param $user_id
 * @return mixed
 */
function get_user_name($user_id) {
    return M('SysUser')->where(array('id' => $user_id))->getField('name');
}


/**
 * 显示价格
 * @param type $num
 * @return type
 */
function show_price($num) {
    return '￥' . number_format($num, 2);
}


/**
 * 浏览链接
 * @param $name
 * @return string
 */
function show_link($name) {
    return '<a href="' . $name . '" target="_blank">' . 'preview' . '</a>';
}

