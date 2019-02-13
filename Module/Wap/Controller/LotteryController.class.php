<?php

namespace Wap\Controller;

/**
 * 大转盘前台控制器
 *
 * @author wupeibo <wupeibo@163.com>
 * @date 2015-04-07
 */
class LotteryController extends WapController {

    protected $ptilte = '大转盘抽奖';

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
    protected $_bind_model = 'AppsLottery';

    /**
     * 活动首页
     */
    protected function _index_filter() {
        //获取活动信息
        $map = array();
        $map['id'] = 1;
        $lottery = M("AppsLottery")->where($map)->find();
        $today = date('Y-m-d');
        $lottery['end'] = 0;
        if ($lottery['status'] != 2 || $today < $lottery['startdate'] || $today > $lottery['enddate']) {
            //活动未开始或已结束
            $lottery['end'] = 1;
        }
        $lottery['usenums'] = M('AppsLotteryRecord')->where(array('openid' => $this->openid, 'create_date' => array('LIKE', date('Y-m-d ') . '%')))->count();

        $record = M('AppsLotteryRecord')->where(array('openid' => $this->openid, 'awards' => array('BETWEEN', '1, 6')))->find();
        $text = ["一等奖：", "二等奖：", "三等奖：", "四等奖：", "五等奖：", "六等奖："];
        $lottery['displayjpnums'] = $record ? 1 : 0;
        $record['name'] = $text[$record['awards'] - 1] . $record['prize'];
        $this->assign("Dazpan", $this->indexlist($lottery));
        $this->assign("record", $record);
    }

    /**
     * 通用抽奖接口
     */
    public function func() {
        if (IS_AJAX && IS_POST) {
            $appinfo = M('AppsLottery')->where(array('id' => 1))->find();
            $usenums = M('AppsLotteryRecord')->where(array('openid' => $this->openid, 'create_date' => array('LIKE', date('Y-m-d ') . '%')))->count();
            if ($usenums >= $appinfo['canrqnums']) { // 抽奖次数不足
                $result['status'] = 5;
                $result['angle'] = 30;
                $result['info'] = '每天只可以抽奖' . $appinfo['canrqnums'] . '次';
                $this->ajaxReturn($result, 'JSON');
            }
            if (!$this->checkcredit($appinfo['kouchujifen'])) {
                $result['status'] = 4;
                $result['angle'] = 30;
                $result['info'] = 'Oh,亲，您当前的积分不足以抽奖';
                $this->ajaxReturn($result, 'JSON');
            }
            $nums = array($appinfo['firstnums'], $appinfo['secondnums'], $appinfo['thirdnums'],
                $appinfo['fournums'], $appinfo['fivenums'], $appinfo['sixnums']);
            $sum = $appinfo['joinnum'];
            //1·查找奖项对应的中奖人数  2·减少该奖项的中奖率
            for ($i = 0; $i < 6; $i++) {
                $hit = M('AppsLotteryRecord')->where(array('awards' => $i + 1, 'type' => 1))->count();
                $nums[$i] -= $hit;
                $sum -= $nums[$i];
            }
            //奖品数组 array ： id|奖品ID min|转盘角度最小值 max|转盘角度最大值 prize|奖品名称 v|中奖概率
            //最后一项为不中奖的概率：maxpoint - ( v1 + v2 + v3 + ... )
            $prize_arr = array(
                '0' => array('id' => 1, 'min' => -14, 'max' => 14, 'prize' => $appinfo['first'], 'v' => $nums[0]),
                '1' => array('id' => 2, 'min' => 46, 'max' => 74, 'prize' => $appinfo['second'], 'v' => $nums[1]),
                '2' => array('id' => 3, 'min' => 106, 'max' => 134, 'prize' => $appinfo['third'], 'v' => $nums[2]),
                '3' => array('id' => 4, 'min' => 166, 'max' => 194, 'prize' => $appinfo['four'], 'v' => $nums[3]),
                '4' => array('id' => 5, 'min' => 226, 'max' => 254, 'prize' => $appinfo['five'], 'v' => $nums[4]),
                '5' => array('id' => 6, 'min' => 286, 'max' => 314, 'prize' => $appinfo['six'], 'v' => $nums[5]),
                '6' => array('id' => 7, 'min' => array(16, 76, 136, 196, 256, 316),
                    'max' => array(44, 104, 164, 224, 284, 344), 'prize' => '', 'v' => $sum)
            );
            foreach ($prize_arr as $val) {
                $arr[$val['id']] = $val['v'];
            }
            //奖项过滤器
            $rid = $this->getRand($arr);
            $res = $prize_arr[$rid - 1]; //中奖项
            $min = $res['min'];
            $max = $res['max'];
            if ($rid == 7) { //没中奖
                //此处设置抽奖不中返回积分
                $i = mt_rand(0, 5);
                $result['angle'] = mt_rand($min[$i], $max[$i]);
                $result['name'] = $res['prize'];
            } else {
                $result['angle'] = mt_rand($min, $max); //随机生成一个角度 
                $resultname = explode('|', $res['prize']);
                $result['name'] = $resultname[1];
            }
            $result['awards'] = $rid;
            $result['rid'] = $this->setRecord($rid,$resultname[1],$resultname[0]);
            if($resultname[0] == 0){
            $result['name'] .= '积分';}
            $result['usenums'] = $usenums + 1;
            $this->ajaxReturn($result, 'JSON');
        }
    }

    /**
     * 保存抽奖结果
     */
    protected function setRecord($rid, $gift, $sncode) {
        $data = array();
        $data['openid'] = $this->openid;
        $data['awards'] = $rid;
        $data['prize'] = $gift;
        $data['type'] = $sncode;
        if ($rid == 7) {
            $data['status'] = 1;
        } else {
            $data['status'] = 2;

            if ($sncode == 0) {
                $data['prize'] .= '积分';
                D('Shop/MemberIntegral')->changeIntegral($this->openid, intval($gift), '转盘抽奖'); // 加积分
            }
        }
        $data['create_date'] = date('Y-m-d H:i:s');
        // $data['type'] = 1;  //真实数据
        return M('AppsLotteryRecord')->add($data);
    }

    /**
     * 概率计算
     * @param type $proArr
     * @return type
     */
    protected function getRand($proArr) {
        $result = '';

        //概率数组的总概率精度 
        $proSum = array_sum($proArr);

        //概率数组循环 
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);

        return $result;
    }

    protected function checkcredit($credit) {
        if ($credit == 0)
            return TRUE;
        $intregral = M("Member")->where(array("openid" => $this->openid))->getField("integral");
        if ($intregral >= $credit) {
            if (D('Shop/MemberIntegral')->changeIntegral($this->openid, $credit, '转盘抽奖')) {
                return TRUE;
            }
            return FALSE;
        }
        return FALSE;
    }

    /* 数据输出之前处理 */

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
