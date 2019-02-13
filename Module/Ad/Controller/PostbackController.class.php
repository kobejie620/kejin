<?php

namespace Ad\Controller;


/**
 * 渠道管理基础控制器类
 * @author zoujingli <rongbin@qq.com>
 * @date 2014/09/04 13:59:00
 */
class PostBackController extends AdController {

    /**
     * 设置模块标题
     * @var type
     */
    public $ptitle = '渠道回发';

    /**
     * 设置模块可访问的操作
     * @var type
     */
    public $access = array(
        'index'  => '渠道回发',
        'edit'   => '编辑回发',
        'add'    => '创建回发',
        'del'    => '删除回发',
        'resume' => '启用回发',
        'forbid' => '禁用回发',
    );

    /**
     * 定义可设置为菜单的节点
     * @var type
     */
    public $menu = array(
        'index' => '渠道回发',
    );


    /**
     * 绑定控制器的模型名称
     * @var type
     */
    protected $_bind_model = "PostBack";


    /**
     * 首页列表前置方法
     * @author zoujingli <rongbin@qq.com>
     * @date 2014/09/04 14:52:23
     */
    public function _before_index() {
        $this->ptitle = '渠道回发';
    }

    /**
     * 列表显示过滤方法
     * @param type $model
     * @param type $map
     */
    protected function _index_filter(&$model, &$map) {
        //设置排序，按照订单生成时间，降序
        val('_order', 'channel_id');
        val('_sort', 0);
    }

    protected function _index_list_filter(&$model) {
        $model->field('pb.*,a.ad_name,c.name as channel_name')
            ->alias('pb')
            ->join('left join __AD__ a on pb.ad_id = a.ad_id')
            ->join('left join __CHANNEL__ c on pb.channel_id = c.channel_id');
    }

    /**
     * 显示之前处理数据
     * @author yangjia
     * @date 2014-11-14
     */
    protected function _data_filter(&$data, &$model) {
        foreach ($data as &$value) {
            $value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
        }
    }

    /**
     * 产品分类编辑前置方法
     * @author zoujingli <rongbin@qq.com>
     * @date 2014/09/04 14:03:58
     */
    protected function _form_filter(&$model, &$data) {
        if (IS_GET) {
            /* 分类数据 */
            $map = array();
            $map['status'] = '2';
            $map['ad_status'] = '1';
            //广告
            $adList = M('Ad')->field('ad_id,ad_name')->where($map)->select();
            array_unshift($adList, array('ad_id' => 0, 'ad_name' => '请选择广告'));
            $this->assign('adList', $adList);

            /* 渠道 */
            $channel_map = array();
            $channel_map['status'] = '2';
            $channel_map['channel_status'] = '1';
            $channelList = M('Channel')->field('channel_id,name as channel_name')->where(array())->select();
            array_unshift($channelList, array('channel_id' => 0, 'channel_name' => '请选择渠道'));
            $this->assign('channelList', $channelList);
        } else {
//            p($_POST);die;
        }

    }

    /**
     * 数据提交成功后的回调 尝试更新关键字
     * @date 2014-11-06
     * @param Model $model
     * @param array $data
     */
    protected function _form_success($model, $data) {

    }

}
