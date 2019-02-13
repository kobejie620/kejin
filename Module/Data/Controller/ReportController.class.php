<?php

namespace Data\Controller;

/**
 * 门店管理控制器
 *
 * @author tanglinjun
 * @date 2014-10-21
 */
class ReportController extends DataController {

    public $ptitle = '转化报表';

    /**
     * 定义可访问的方法名
     * @var type
     */
    public $access = array(
        'index' => '转化报表',
        'export'    => '数据导出',
    );

    /**
     * 设定可设置为菜单的节点
     * @var type
     */
    public $menu = array(
        'index' => '转化报表'
    );

    /**
     * 绑定操作模型
     * @var type
     */
    protected $_bind_model = 'AdReport';

    /**
     * 首页列表前置方法
     *
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    public function _before_index() {
        $this->ptitle = '转化报表';
    }

    /**
     * 列表显示过滤方法
     * @param type $model
     * @param type $map
     */
    protected function _index_filter(&$model, &$map) {
        //设置排序，按照订单生成时间，降序
        val('_order', 'report_id');
        val('_sort', 0);
    }


    /**
     * 产品分类编辑前置方法
     *
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:03:58
     */
    protected function _form_filter(&$model, &$data) {

    }

    /**
     * 记录导出方法
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
        $filename = "转化报表-" . date("Y-m-d");
        //导出表格
        $excel = new Excel();
        $excel->renderData($data)->download($filename);
    }

    /**
     * 广告追踪回调
     */
    public function report() {
        p("fuck");
    }
}
