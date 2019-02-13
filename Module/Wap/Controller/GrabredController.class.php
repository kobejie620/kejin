<?php

namespace Wap\Controller;

class GrabredController extends WapController {

    /**
     * 红包入口
     */
    public function index() {
        $this->display();
    }

    /*     * 程序处理* */

    public function grab() {
        /* 读取配置 */
        $list = M("AppsRed")->find(1);
        $time = date('Y-m-d');
        if ($list['status'] == 1) {
            /* y用户当天抽取的次数 */
            $num = M('WechatRedPacket')->where(array('openid' => session('openid'), 'send_time' => array('like', $time . '%')))->count();
            if ($num >= $list['count']) {
                $data['info'] = '不好意思你今天领取红包的次数已达到上限';
                $this->ajaxReturn($data);
                exit;
            }
            /* 开始发红包 */
            $grabred = new \Wechat\Model\WechatRedPacketModel();
            $openid = session('openid');
            $send_name = '精典妆家';
            $total_amount = rand(1, $list['max_red']);
            $wishing = '恭喜你获得精典妆家红包';
            $act_name = $list['title'];
            $remark = $list['info'];
            $type = '1';
            $type_id = 'NORMAL';
            $info = $grabred->grantRedPocket($openid, $send_name, $total_amount, $wishing, $act_name, $remark, $type, $type_id);
            $this->ajaxReturn($info);
        } else {
            $data['info'] = $list['endinfo'];
            $this->ajaxReturn($data);
        }
    }
    /*卡券*/
        /**
     * 红包入口
     */
    public function card() {
        //微信卡卷
        $wechat = new \Wechat\Model\WechatCardModel();
        //选择卡卷
        $this->assign('kajuan', $wechat->chooseCard());
        //添加卡卷
        $temp = M("WechatCard")->where(array("audit_status" =>'CARD_STATUS_VERIFY_OK'))->field("card_id")->find();
        $this->assign('card_config', $wechat->bacthAddCard($temp['card_id']));
        $this->display();
    }
    

}
