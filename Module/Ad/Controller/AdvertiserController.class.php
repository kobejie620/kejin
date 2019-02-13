<?php

namespace Ad\Controller;

class AdvertiserController extends AdController {

    /**
     * 设置模块标题
     * @var type
     */
    public $ptitle = '广告商管理';

    /**
     * 设置模块可访问的操作
     * @var type
     */
    public $access = array(
        'index'  => '广告商列表',
        'edit'   => '编辑广告商',
        'add'    => '添加广告商',
        'del'    => '删除广告商',
        'resume' => '启用广告商',
        'forbid' => '禁用广告商',
        'examine' => '待定广告商',
    );

    /**
     * 定义可设置为菜单的节点
     * @var type
     */
    public $menu = array(
        'index' => '广告商列表',
        'examine' => '待定广告商',
    );

    /**
     * 绑定控制器的模型名称
     * @var type
     */
    protected $_bind_model = "Advertiser";


    /**
     * 首页列表前置方法
     *
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    public function _before_index() {
        $this->ptitle = '广告商管理';
    }

    /**
     * 列表显示过滤方法
     * @param type $model
     * @param type $map
     */
    protected function _index_filter(&$model, &$map) {
        $map['advertiser_status'] = 1 ;
        //设置排序，按照订单生成时间，降序
        val('_order', 'advertiser_id');
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
            //添加ad_sn  1 开头代表广告  2 开头代表广告商  3开头代表渠道
            $uuid = "2" . str_pad("{$data['id']}", 2, '0', STR_PAD_LEFT). date('ymdH') ;
            $this->_save(array('advertiser_sn' => $uuid), D('Advertiser'),array('advertiser_id' => $data['id']));
        }

    }

    /**
     * 删除显示过滤方法
     * @param $model
     * @param $map
     */
    protected function _del_filter(&$model,&$map) {

    }

    /**
     * 数据被删除后的回调 尝试删除关键字
     * @param \Think\Model $model
     * @param array $ids
     */
    protected function _del_success($model, $ids) {
//        foreach ($ids as $id) {
//            $data = array();
//            $data['id'] = $id;
//            $this->_writeKeys($model, $data, 'news');
//        }
    }


    /**
     * 待定广告商
     */
    public function examine() {
        $this->ptitle = "广告商审核";
        $this->assign('ptitle', $this->ptitle);
        $this->_bind_model = 'Advertiser';
        parent::index();
    }

    /**
     * 列表显示过滤方法
     * @param type $model
     * @param type $map
     */
    protected function _examine_filter(&$model, &$map) {
        $map['advertiser_status'] = 0 ;
    }

    /**
     * 广告商审核
     */
    public function to_examine() {
        $advertiser_id = $_GET['id'];
        $info['advertiser_status'] = 1 ;
        $res = M('Advertiser')->where(array('advertiser_id' => $advertiser_id))->save($info);
        if($res) {
            $this->success("审核通过");
        } else {
            $this->success("审核失败");
        }
    }
}
