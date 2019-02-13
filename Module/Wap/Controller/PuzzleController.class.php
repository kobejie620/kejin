<?php

namespace Wap\Controller;

/**
 * 拼图控制器类
 * @author jindewen <jindewen@21cn.com>
 * @date 2015-03-26 17:40
 */
class PuzzleController extends WapController {

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
    protected $_bind_model = 'AppsPuzzle';

    protected function _index_filter() {
        $this->getMember($this->openid);
        $puzzle = (array) M('AppsPuzzle')->find(I('id', 1, 'intval'));
        $map = array();
        $map['openid'] = $this->openid;
        $map['create_date'] = array('link', date('Y-m-d%'));
        $puzzle['count'] = M('AppsPuzzle')->where($map)->count();
        $puzzle['mytimes'] = $puzzle['times'] - $puzzle['count'] < 1 ? 0 : $puzzle['times'] - $puzzle['count'];
        $this->assign('puzzle', $puzzle);
    }

    /**
     * 排行榜
     */
    public function chart() {
        $WechatMemberTable = M('WechatMember')->getTableName();
        $recordTable = M('AppsPuzzleRecord')->getTableName();
        $map['a.id'] = array('exp', "=(SELECT id FROM {$recordTable} temp WHERE a.openid = temp.openid ORDER BY temp.step,temp.time LIMIT 1)");
        $this->list = M('AppsPuzzleRecord')->alias('a')
                        ->field('a.*,w.nickname,w.headimgurl')
                        ->where($map)
                        ->join("LEFT JOIN {$WechatMemberTable} w ON w.openid = a.openid")
                        ->order('step,time')
                        ->limit(10)->select();
        $this->display();
    }

    /**
     * 拼图完成
     */
    public function finish() {
        if (IS_POST && IS_AJAX) {
            $openid = $this->openid;
            empty($openid) && $openid = I('openid');
            $step = I('step');
            $time = I('time');
            $data = array(
                'openid' => $openid,
                'step'   => $step,
                'time'   => $time,
                'ctime'  => time()
            );
            M('AppsPuzzleRecord')->add($data);
            $integral = M('AppsPuzzle')->where(array('id' => I('id', 1, 'intval')))->getField('integral');
            if (!empty($integral)) {
                D('Shop/MemberIntegral')->changeIntegral($this->openid, $integral, '拼图游戏');
            }
            $return = array('msg' => "恭喜你完成拼图游戏：\n步数：{$step}，用时：{$time}！", 'url' => U('chart'));

            $this->ajaxReturn($return);
        }
    }

}
