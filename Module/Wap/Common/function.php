<?php

/**
 * 会员积分变更
 * @author jindewen
 * 减少积分只影响可用积分，增加积分同时影响总积分，同时生成记录
 * @param int $integral 变化的积分（可以是负数）
 * @param string $type 积分变化类型
 */
function change_member_integral($integral, $type = '其他途径', $openid = null) {
    $openid = is_null($openid) ? $_SESSION["wap"]["openid"] : $openid;
    $map = array('openid' => $openid);
    D('Member')->where($map)->setInc('integral', $integral); // 改变积分
    if ($integral > 0) {
        D('Member')->where($map)->setInc('total_integral', $integral); // 改变总积分
    } else {
        $current_integral = D('Member')->where($map)->getField('integral');
        if ($current_integral < abs($integral)) {
            return false; // 积分不足
        } else {
            D('Member')->where($map)->setDec('used_integral', $integral); // 改变消费积分
        }
    }
    return D('IntegralRecord')->add(array('integral' => $integral, 'type' => $type, 'openid' => $openid)); // 积分记录
}
