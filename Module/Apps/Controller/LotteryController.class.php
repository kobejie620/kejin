<?php

namespace Apps\Controller;

use Library\Util\Excel;
use Think\Model;

/**
 * 大转盘后台控制器
 *
 * @author tanglinjun <99439593@qq.com>
 * @date 2014-11-05
 */
class LotteryController extends AppsController {

    /**
     * 绑定控制器
     * @var type 
     */
    protected $_bind_model = 'AppsLottery';

    /**
     * 定义模块代码
     * @var type 
     */
    public $ptitle = '大转盘设置';

    /**
     * 定义可访问的操作方法
     * @var type 
     */
    public $access = array(
        'index' => '配置信息',
        'edit' => '修改配置',
        'exporting' => '导出Excel',
        'record' => '中奖记录',
    );

    /**
     * 定义可定义为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '配置列表',
        'record' => '中奖记录'
    );

    /**
     * 显示配置
     */
    public function index() {
        $list = $this->indexlist(M("AppsLottery")->find(1));
        $this->assign("vo", $list);
        $this->assign('ptitle', $this->ptitle);
        $this->display();
    }

    /* 数据存入之前的操作 */

    public function _edit_filter($model, &$vo) {
        p($vo);
        $vo['first'] = $vo['type'][0] . '|' . $vo['first'];
        $vo['second'] = $vo['type'][1] . '|' . $vo['second'];
        $vo['third'] = $vo['type'][2] . '|' . $vo['third'];
        $vo['four'] = $vo['type'][3] . '|' . $vo['four'];
        $vo['five'] = $vo['type'][4] . '|' . $vo['five'];
        $vo['six'] = $vo['type'][5] . '|' . $vo['six'];
    }
        /* 输出之前改造 */

    public function indexlist(&$vo) {
        $vo['first'] = explode('|', $vo['first']);
        $vo['second'] = explode('|', $vo['second']);
        $vo['third'] = explode('|', $vo['third']);
        $vo['four'] = explode('|', $vo['four']);
        $vo['five'] = explode('|', $vo['five']);
        $vo['six'] = explode('|', $vo['six']);
        return $vo;
    }
    /**
     * 数据提交成功后的回调 尝试更新关键字
     * 
     * @date 2014-11-06
     * @param Model $model
     * @param array $data
     */
    protected function _form_success($model, $data) {
        $data['url'] = to_domain(U('wap/Lottery/index', array('id' => $data['id'])));
        $this->_writeKeys($model, $data, 'news');
    }

    /**
     * 大转盘中奖记录列表
     * 
     * @author tanglinjun
     * @date 2014-11-07
     */
    public function record() {
        $this->ptitle = '大转盘中奖记录';
        parent::index(M('AppsLotteryRecord'));
    }

    /**
     * 查询过滤
     * @param type $model
     * @param type $map
     */
    protected function _record_list_filter(&$model, &$map) {
        $map['r.prize'] = array(array('neq', ''), array('exp', 'is not null'), 'and');
        $model->alias('r')
                ->field('r.id,m.name,wm.nickname,wm.headimgurl,r.prize,m.phone,wm.province,wm.city,r.create_date')
                ->join('left join __MEMBER__ m on m.openid=r.openid')
                ->join('left join __WECHAT_MEMBER__ wm on wm.openid=m.openid');
    }

    /**
     * 记录导出方法
     * 
     * @author tanglinjun
     * @date 2014-11-07
     */
    public function exporting() {
        //拼装数据
        $map = array();
        $map['r.prize'] = array(array('neq', ''), array('exp', 'is not null'), 'and');
        $data = M("AppsLotteryRecord")
                ->field('m.name,wm.nickname,r.prize,m.phone,wm.province,wm.city,r.create_date')
                ->alias('r')
                ->join('left join __MEMBER__ m on m.openid=r.openid')
                ->join('left join __WECHAT_MEMBER__ wm on wm.openid=m.openid')
                ->where($map)
                ->select();
        $header = array('姓名', '微信昵称', '中奖内容', '联系电话', '省份', '城市', '中奖时间');
        array_unshift($data, $header);
        $filename = "大转盘-" . date("Y-m-d");
        //导出表格
        $excel = new Excel();
        $excel->renderData($data)->download($filename);
    }

}
