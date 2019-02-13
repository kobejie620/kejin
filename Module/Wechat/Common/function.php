<?php

/**
 * 微信菜单控制显示
 * 
 * @param type $id 菜单ID
 * @param type $vo 菜单记录
 * @return string
 */
function show_menu_button($id, $vo) {
    $button = array();
    if ($vo['type'] === 'null' && intval($vo['pid']) === 0 && count($vo['lower']) < 6) {
        $button[] = show_load_button($id, $vo, '添加子菜单', 'Wechat/Menu/add', array('pid' => $id));
    } else {
        $button[] = '<a class="btn btn-xs btn-link disabled">添加子菜单</a> ';
    }
    $button[] = show_status_button(join(',', $vo['lower']), $vo);
    $button[] = show_load_button($id, $vo, '编辑', 'Wechat/Menu/edit');
    $button[] = show_del_button(join(',', $vo['lower']), $vo, '删除');
    return join('', $button);
}

/**
 * 生成关键字二维码
 * @param type $keys
 * @return type
 */
function create_qrc($keys) {
    if (!empty($keys)) {
        $map = array();
        $map['keys'] = $keys;
        $map['qrc'] = array(array('neq', ''), array('exp', 'is not null'), 'and');
        $url = M('WechatKeys')->where($map)->getField('qrc');
        if (!empty($url)) {
            return $keys . " <img class='fancy' data-src='{$url}&_name=qrc.jpg' style='height:14px' src='{$url}&_name=qrc.jpg'/>";
        }
        if (auth('Wechat/Keys/createQrc')) {
            $url = U('Wechat/Keys/createQrc', array('keys' => $keys));
            return $keys . " <a class='fr' href='javascript:void(0)' title='生成二维码' data-load='{$url}'><i class='glyphicon glyphicon-qrcode'></i></a>";
        }
    }
    return $keys;
}

/**
 * 显示卡券类型
 * @param type $value
 * @param type $vo
 * @return type
 */
function show_card_type_text($value) {
    switch ($value) {
        case 'GROUPON': return '团购券';
        case 'CASH': return '代金券';
        case 'DISCOUNT': return '折扣券';
        case 'GIFT': return '礼品券';
        case 'GENERAL_COUPON': return '优惠券';
    }
}

/**
 * 卡券有效时间
 * @param type $value
 * @return type
 */
function show_date_info_text($value) {
    $date_info = json_decode($value, true);
    if ($date_info['type'] == 'DATE_TYPE_FIX_TIME_RANGE ') {
        return '在' . date('Y-m-d', $date_info['begin_timestamp']) . '至' . date('Y-m-d', ($date_info['end_timestamp'] - 3600 * 24)) . '期间有效';
    } else {
        ($date_info['fixed_begin_term'] == 0) && $date_info['fixed_begin_term'] = '当天';
        return '领取后' . $date_info['fixed_begin_term'] . '生效' . $date_info['fixed_term'] . '天有效';
    }
}

/**
 * 卡券审核状态
 * @param type $value
 * @return string
 */
function show_examine_status_text($value) {
    switch ($value) {
        case 'CARD_STATUS_VERIFY_OK':return '通过审核';
        case 'CARD_STATUS_VERIFY_FAIL':return '审核失败';
        case 'CARD_STATUS_DELETE':return '卡券已删除';
        case 'CARD_STATUS_DISPATCH':return '在公众平台投放过的卡券';
        default:return '待审核';
    }
}