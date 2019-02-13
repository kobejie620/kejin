<?php

namespace Apps\Controller;

/**
 * 有奖问答控制器
 * @author jindewen <jindewen@21cn.com>
 * @date 2015/03/31 15:51:47
 */
class RedController extends AppsController {

    /**
     * 绑定模型名
     * @var type
     */
    protected $_bind_model = 'AppsRed';

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = "红包设置";

    /**
     * 设置模块可访问的节点
     * @var type 
     */
    public $access = array(
        'index' => '问题列表',
        'edit' => '编辑问题',
        'redlist' => '领取红包列表'
    );

    /**
     * 设置可配置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '问题列表',
        'redlist' => '领取红包列表'
    );

    public function index() {
        $list = M("AppsRed")->find(1);
        $this->assign("vo", $list);
        $this->assign('ptitle', $this->ptitle);
        $this->display();
    }

    public function redlist() {
        $this->_bind_model = 'WechatRedPacket';
        parent::index();
    }

    protected function _redlist_data_filter(&$data, $model) {
        foreach ($data as $key => &$value) {
            $value['name'] = M('Member')->where(array('openid' => $value['openid']))->getField('nickname');
            $value['total_amount'] = $value['total_amount'] / 100;
            $value['status'] = red_status( $value['status']);
        }
    }

    protected function _redlist_filter($model, &$map) {
        $map['type'] = array('eq', '1');
    }

}
