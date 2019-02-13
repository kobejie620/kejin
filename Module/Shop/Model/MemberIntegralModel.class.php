<?php

namespace Shop\Model;

class MemberIntegralModel extends \Think\Model {

    /**
     * 变更会积分，同步记录历史
     * @param type $openid 会员openid
     * @param type $integral 变更的积分,--\d 表示回退
     * @param type $desc 变更说明
     * @param type $trans 事务开关 true
     */
    public function changeIntegral($openid, $integral, $desc, $trans = true) {
        if (empty($integral)) {
            return null;
        }
        $mode = 'null';
        if (is_string($integral) && stripos($integral, '--') !== false) {
            $mode = 'rollback';
            $integral = str_replace('--', '', $integral);
        }
        $data = array();
        $data['openid'] = $openid;
        $data['integral'] = intval($integral);
        $data['desc'] = $desc;
        $trans && $this->startTrans();
        $result = $this->add($data);
        if ($result === false) {
            $trans && $this->rollback();
            return false;
        }
        $map = array();
        $map['openid'] = $openid;
        if ($data['integral'] > 0) {
            if ($mode === 'rollback') {
                $r1 = true;
                $r3 = M('Member')->where($map)->setInc('used_integral', -intval($integral));
            } else {
                $r1 = M('Member')->where($map)->setInc('total_integral', intval($integral));
                $r3 = true;
            }
            $r2 = M('Member')->where($map)->setInc('integral', intval($integral));
        } else {
            $r1 = true;
            $r2 = M('Member')->where($map)->setInc('integral', intval($integral));
            $r3 = M('Member')->where($map)->setInc('used_integral', -intval($integral));
        }
        if ($r1 !== false && $r2 !== false && $r3 !== false) {
            $trans && $this->commit();
            return true;
        }
    }

}
