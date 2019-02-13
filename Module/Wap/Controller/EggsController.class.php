<?php

namespace Wap\Controller;

class EggsController extends WapController {

    /**
     * 用户信息检测
     */
    public function _initialize() {
        parent::_initialize();
        $this->getMember($this->oauth(), null, false);
    }

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'AppsEggs';

    /**
     * 显示活动列表
     */
    public function index() {
        $map = array();
        $map['openid'] = $this->openid;
        $map['create_date'] = array('like', date('Y-m-d%'));
        $where = array();
        $where['openid'] = $this->openid;
        $where['prize'] = array('neq', '');
        $recode = M('AppsEggsRecord')->where($where)->order('id desc')->select();
        $this->assign('recode', $recode);
        $this->assign('usenums', (int) M('AppsEggsRecord')->where($map)->count());
        $index = $this->indexlist(M('AppsEggs')->where(array('id' => '1'))->find());
        $this->assign('vo', $index);
        $this->display();
    }

    /**
     * 添加记录
     */
    public function add_eggs() {
        $vo = M('AppsEggs')->find('1');
        $map = array();
        $map['openid'] = $this->openid;
        $map['create_date'] = array('like', date('Y-m-d%'));
        if ($vo['canrqnums'] <= M('AppsEggsRecord')->where($map)->count()) {
            $msg = array();
            $msg['status'] = 0;
            $msg['info'] = '每天只需要参与' . $vo['canrqnums'] . '次活动';
            $this->ajaxReturn($msg);
            die();
        }
        $result = $this->_rate_gift($vo);
        $data = array();
        $data['openid'] = $this->openid;
        $result['prize'] = explode('|', $result['prize']);
        $data['type'] = $result['prize'][0];
        $data['prize'] = $result['prize'][1];
        $data['name'] = $result['name'];
        if (false !== M('AppsEggsRecord')->add($data)) {
            if ($result['status'] === 1) {
                $msg = array();
                $msg['status'] = 1;
                $msg['title'] = '未领取到积分';
                $msg['info'] = '很遗憾，这次没有中奖。';
                $this->ajaxReturn($msg);
                die();
            } elseif ($result['status'] === 2) {
                $msg = array();
                $msg['status'] = 2;
                $msg['title'] = '领取积分成功';
                if ($result['prize'][0] == 0) {
                    $msg['info'] = '恭喜您，中了' . $result['name'] . '，获得了' . $result['prize'][1] . '积分';
                    D('Shop/MemberIntegral')->changeIntegral($this->openid, intval($result['prize']), '砸金蛋');
                } else {
                    $msg['info'] = '恭喜您，中了' . $result['name'] . '，获得了' . $result['prize'][1];
                }
                $this->ajaxReturn($msg);
                die();
            }
        }
        $msg = array();
        $msg['status'] = 1;
        $msg['title'] = '系统开小菜了~_~';
        $msg['info'] = '操作失败，请稍候再试';
        $this->ajaxReturn($msg);
        die();
    }

    /**
     * 抽奖操作
     * @return type
     */
    protected function _rate_gift($vo) {

        $rates = array(
            'first' => $vo['first_rate'],
            'second' => $vo['second_rate'],
            'third' => $vo['third_rate'],
            'four' => $vo['four_rate'],
            'five' => $vo['five_rate'],
            'six' => $vo['six_rate']
        );
        $names = array(
            'first' => '一等奖',
            'second' => '二等奖',
            'third' => '三等奖',
            'four' => '四等奖',
            'five' => '五等奖',
            'six' => '六等奖'
        );
        $ranges = array();
        $rateOffset = 0;
        foreach ($rates as $key => $rate) {
            if (!empty($rate)) {
                $ranges[$key] = array($rateOffset, $rateOffset+=$rate);
            }
        }
        $rand = rand(0, array_sum($rates) * 100) / 100;
        $result = '';
        foreach ($ranges as $key => $range) {
            if ($rand >= $range[0] && $rand <= $range[1]) {
                $result = $key;
            }
        }
        if (empty($result) || empty($vo[$result])) {
            return array('status' => 1, 'prize' => 0, 'name' => '未中奖');
        } else {
            return array('status' => 2, 'prize' => $vo[$result], 'name' => $names[$result]);
        }
    }

    public function indexlist(&$vo) {
        $vo['first'] = explode('|', $vo['first']);
        $vo['second'] = explode('|', $vo['second']);
        $vo['third'] = explode('|', $vo['third']);
        $vo['four'] = explode('|', $vo['four']);
        $vo['five'] = explode('|', $vo['five']);
        $vo['six'] = explode('|', $vo['six']);
        return $vo;
    }

}
