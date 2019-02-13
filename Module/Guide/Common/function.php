<?php

/**
 * 公司最终授权状态
 * @param type $type_id
 * @return type
 */
function get_company_type($type_id = null) {
	$status = array(
		'1' => '等待门店审批',
		'2' => '门店审核未通过',
		'3' => '门店审核通过'
	);
	return is_null($type_id) ? $status : $status[$type_id];
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
