<?php

namespace Apps\Controller;

/**
 * 品牌信赖控制器
 * @author jindewen <jindewen@21cn.com>
 * @date 2015/03/31 15:51:47
 */
class PuzzleController extends AppsController {

    /**
     * 绑定模型名
     * @var type 
     */
    protected $_bind_model = 'AppsPuzzle';

    /**
     * 设置模块名称
     * @var type 
     */
    public $ptitle = "拼图游戏";

    /**
     * 设置模块可访问的方法
     * @var type 
     */
    public $access = array(
        'record' => '记录列表',
        'index'  => '拼图游戏',
        'edit'   => '配置游戏',
    );

    /**
     * 设置可配置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index'  => '拼图游戏',
        'record' => '记录列表',
    );

    /**
     * 显示配置
     */
    public function index() {
        $this->assign("info", M("AppsPuzzle")->find(1));
        $this->assign('ptitle', $this->ptitle);
        $this->display();
    }

    /**
     * 拼图游戏记录
     */
    public function record() {
        $this->ptitle = '拼图游戏记录';
        parent::index(M('AppsPuzzleRecord'));
    }

    /**
     * 列表查询操作
     * @param type $model
     * @param type $map
     */
    protected function _record_filter(&$model, &$map) {
        /* 查询条件 */
        ($kw = I("get._kw")) && $map['wm.nickname|m.name|m.phone'] = array("like", "%$kw%");
    }

    /**
     * 列表关联查询
     * @param type $model
     */
    protected function _record_list_filter(&$model) {
        $model->field('a.*,wm.nickname,m.name,m.phone')
                ->join("LEFT JOIN __WECHAT_MEMBER__ wm ON wm.openid = a.openid")
                ->join("LEFT JOIN __MEMBER__ m ON m.openid = wm.openid")
                ->order('time,step')
                ->alias('a');
    }

    /**
     * 删除记录
     */
    public function del() {
        $this->_bind_model = 'AppsPuzzleRecord';
        parent::del();
    }

}
