<?php

/**
 * 获取产品分类信息
 * 
 * @param type $id
 * @return type
 */
function get_product_name($id) {
    return M('StoreProductCat')->where(array('id' => $id))->getField('name');
}

/**
 * 获取订单详细链接
 * 
 * @param type $order_no
 * @return type
 */
function get_order_link($order_no, $vo = '') {
    return '<a href="javascript:void(0)" data-load="' . U('Store/Order/edit', array('order_no' => $order_no, "id" => $vo['id'])) . '">' . $order_no . '</a>';
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
 * 显示订单发货送状态
 * @param type $status
 * @return string
 */
function show_order_send_status($status) {
    //配送状态 0：未发送,1：已发送,2：已经签收
    switch (intval($status)) {
        case 0:
            return '未发货';
        case 1;
            return '已发货';
        case 2:
            return '已签收';
        default :
            return '状态异常';
    }
}

/**
 * 显示订单状态
 * @param type $status
 * @return string
 */
function show_order_status($status) {
    //订单状态 1生成订单,2支付订单,3取消订单,4作废订单,5完成订单
    switch (intval($status)) {
        case 1;
            return '新订单';
        case 2:
            return '已确认';
        case 3:
            return '已取消';
        case 4:
            return '已退款';
        case 5:
            return '已完成';
        default :
            return '状态异常';
    }
}

/**
 * 显示支付状态
 * @param type $status
 * @return string
 */
function pay_status($status) {
    //订单状态 1生成订单,2支付订单,3取消订单,4作废订单,5完成订单
    switch (intval($status)) {
        case 0;
            return '未支付';
        case 1:
            return '已支付';
        case 2:
            return '已退款';
        default :
            return '不需要支付';
    }
}

/**
 * 显示订单行颜色
 * @param type $status
 * @param type $vo
 * @return string
 */
function show_order_color($status, $vo) {
    if (in_array(intval($status), array(1))) {
        return '#31B0D5;';
    } elseif (in_array(intval($status), array(3))) {
        return '#D9534F;';
    } elseif (in_array(intval($status), array(4))) {
        return '#666666;';
    }elseif (in_array(intval($status), array(5))) {
        return '#5BAEA3;';
    }
    if (intval($vo['pay_status']) === 1) {
        return '#F0AD4E;';
    }
    if (intval($vo['send_status']) === 1) {
        return '#337AB7;';
    }
    if (intval($vo['send_status']) === 2) {
        return '#5CB85C;';
    }
}

/**
 * 订单改价
 * 
 * @param type $order_id
 * @return type
 */
function change_order_price($order_id, $vo, $title) {
    if ($vo['status'] == 1) {
        return '<a class="btn btn-xs btn-default" href="javascript:void(0)" data-load="' . U('Store/Order/reduce', array('id' => $order_id)) . '">' . $title . '</a>';
    } else {
        return '';
    }
}

/**
 * 是否参与积分兑换
 * 
 * @author tanglinjun
 * @date 2015-01-22
 */
function show_point_status($point) {
    if ($point == '1') {
        return '是';
    } elseif ($point == '0') {
        return '否';
    }
}

/**
 * 积分订单取货方式显示
 * @autor tanglinjun
 * @date 2014-01-23
 */
function show_get_method($status) {
    if ($status == '1') {
        return "自己提货";
    } elseif ($status == '2') {
        return "快递配送";
    }
}

/**
 * 获取积分订单详细链接
 * 
 * @param type $order_no
 * @return type
 */
function get_credit_link($id, $vo) {
    return '<a href="javascript:void(0)" data-load="' . U('Store/credit/edit', array('id' => $id)) . '">' . $vo['credit_no'] . '</a>';
}

/**
 * 积分订单改价
 * 
 * @param type $order_id
 * @return type
 */
function change_credit_price($credit_id, $vo, $title) {
    if ($vo['status'] == 1 && $vo['pay_type'] > 0) {
        return '<a class="btn btn-xs btn-default" href="javascript:void(0)" data-load="' . U('Store/Credit/reduce', array('id' => $credit_id)) . '">' . $title . '</a>';
    } else {
        return '';
    }
}

/**
 * 查询需要支付的金额
 * 
 * @param type $order_id
 * @return type
 */
function get_credit_price($id, $vo, $title) {
    if ($vo['pay_type'] > 0) {
        return sprintf("%.2f", $vo['order_amount'] + $vo['pay_freight'] - $vo['promotions']);
    } else {
        return 0;
    }
}

/**
 * 给产品名称添加产品详情页链接
 */
function show_alink($name, $vo) {
    $url = U('Shop/Index/edit', array('id' => $vo['id']), true, true);
    return '<a href="' . $url . '" target="_blank" style="color:inherit;">' . $name . '</a>' .
            " <a class='fr' href='javascript:void(0)' title='复制链接' data-copy='{$url}'><i class='glyphicon glyphicon-copy'></i></a>";
}

/**
 * 显示订单信息
 * @param type $value
 * @param type $vo
 * @return type
 */
function show_order_price_info($value, $vo) {
    return '订单金额：' . show_price($value) .
            ($vo['pay_freight'] == 0 ? '' : ('<br/>运费金额：' . show_price($vo['pay_freight']))) .
            ($vo['promotions'] == 0 ? '' : ('<br/>优惠金额：' . show_price($vo['promotions']))) .
            '<br /><span style="font-weight:700">' .
            (in_array($vo['pay_status'], array('1')) ? '实付金额：' : '需付金额：') .
            show_price($value + $vo['pay_freight'] - $vo['promotions']) . '</span>';
}

/*
 * 门店成绩详情
 */
function show_storescore_info($sid) {
    $url = U('Store/Stores/score', array("sid" => $sid));
    return "<a class='btn btn-xs btn-link' data-href='{$url}'>详情</a>";
}

/**
 * 门店兑换记录详情
 */
function show_storerecord_info($store_id) {
    $url = U('Store/Stores/storesrecord', array("store_id" => $store_id));
    return "<a class='btn btn-xs btn-link' data-href='{$url}'>详情</a>";
}

function get_tixian_status($type_id = null) {
	$status = array(
		'1' => '未提现',
		'2' => '已提现',
		'3' => '等待审批',
		'4' => '申请被驳回',
		'5' => '退款取消的提现',
		'6' => '等待订单完成的备用状态'
	);
	return is_null($type_id) ? $status : $status[$type_id];
}
