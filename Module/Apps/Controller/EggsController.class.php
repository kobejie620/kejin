<?php

namespace Apps\Controller;

/**
 * 砸金蛋
 */
class EggsController extends AppsController {

    /**
     * 绑定控制器
     * @var type 
     */
    protected $_bind_model = 'AppsEggs';

    /**
     * 定义模块代码
     * @var type 
     */
    public $ptitle = '砸金蛋配置';

    /**
     * 定义可访问的操作方法
     * @var type 
     */
    public $access = array(
        'index' => '配置信息',
        'edit' => '修改配置',
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
        $list = M("AppsEggs")->find(1);
        $list = $this->indexlist($list);
        $this->assign("vo", $list);
        $this->assign('ptitle', $this->ptitle);
        $this->display();
    }

    /* 存入数据库之前组装 */

    public function _edit_filter($model, &$vo) {
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
     * 中奖记录
     */
    public function record() {
        $this->ptitle = ' 获奖的名单';
        $this->_bind_model = 'AppsEggsRecord';
        parent::index();
    }

    /**
     * 中奖记录过滤
     * @param \Think\Model $model
     */
    protected function _record_list_filter(&$model) {
        $model->alias('e')->field('e.*,wm.nickname,m.phone,m.name username')
                ->join('left join __WECHAT_MEMBER__ wm on e.openid=wm.openid')
                ->join('left join __MEMBER__ m on e.openid=m.openid');
    }

}
