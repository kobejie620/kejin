<?php

namespace Channel\Controller;


/**
 * 渠道管理基础控制器类
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 13:59:00
 */
class IndexController extends ChannelController {

    /**
     * 设置模块标题
     * @var type
     */
    public $ptitle = '渠道列表';

    /**
     * 设置模块可访问的操作
     * @var type
     */
    public $access = array(
        'index'  => '渠道列表',
        'edit'   => '编辑渠道',
        'add'    => '添加渠道',
        'del'    => '删除渠道',
        'resume' => '启用渠道',
        'forbid' => '禁用渠道',
        'examine' => '待定渠道',
    );

    /**
     * 定义可设置为菜单的节点
     * @var type
     */
    public $menu = array(
        'index' => '渠道列表',
        'examine' => '待定渠道',
    );

    /**
     * 绑定控制器的模型名称
     * @var type
     */
    protected $_bind_model = "Channel";


    /**
     * 首页列表前置方法
     *
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    public function _before_index() {
        $this->ptitle = '渠道列表';
    }

    /**
     * 列表显示过滤方法
     * @param type $model
     * @param type $map
     */
    protected function _index_filter(&$model, &$map) {
        $map['channel_status'] = 1 ;
        //设置排序，按照订单生成时间，降序
        val('_order', 'channel_id');
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
     * 数据提交成功后的回调 尝试更新关键字
     * @date 2014-11-06
     * @param Model $model
     * @param array $data
     */
    protected function _form_success($model, $data) {
        if($data['id']) {
            //添加ad_sn  1 开头代表广告  2 开头代表广告主  3开头代表渠道
            $uuid = "3" . str_pad("{$data['id']}", 2, '0', STR_PAD_LEFT). date('ymdH') ;
            $this->_save(array('channel_sn' => $uuid), D('Channel'),array('channel_id' => $data['id']));
        }

    }

    /**
     * 待定渠道
     */
    public function examine() {
        $this->ptitle = "渠道审核";
        $this->assign('ptitle', $this->ptitle);
        $this->_bind_model = 'Channel';
        parent::index();
    }


    protected function _examine_filter(&$model, &$map) {
        $map['channel_status'] = 0 ;
    }


    /**
     * 广告审核
     */
    public function to_examine() {
        $channel_id = $_GET['id'];
        $info['channel_status'] = 1 ;
        $res = M('Channel')->where(array('channel_id' => $channel_id))->save($info);
        if($res) {
            $this->success("审核通过");
        } else {
            $this->success("审核失败");
        }
    }
}
