<?php

namespace Ad\Controller;

class AdvertisementController extends AdController {

    /**
     * 设置模块标题
     * @var type
     */
    public $ptitle = '广告管理';

    /**
     * 设置模块可访问的操作
     * @var type
     */
    public $access = array(
        'index'  => '广告列表',
        'edit'   => '编辑广告',
        'add'    => '添加广告',
        'del'    => '删除广告',
        'resume' => '启用广告',
        'forbid' => '禁用广告',
        'track'   => '追踪链接',
        'examine' => '待定广告',
    );

    /**
     * 定义可设置为菜单的节点
     * @var type
     */
    public $menu = array(
        'index' => '广告列表',
        'examine' => '待定广告',
    );

    /**
     * 绑定控制器的模型名称
     * @var type
     */
    protected $_bind_model = "Ad";


    /**
     * 首页列表前置方法
     *
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    public function _before_index() {
        $this->ptitle = '广告管理';
    }

    /**
     * 列表显示过滤方法
     * @param type $model
     * @param type $map
     */
    protected function _index_filter(&$model, &$map) {
        $map['ad_status'] = 1 ;
        $user = session('user');
        if($user['role'] > '1') {      //其他商务
            $map['user_id'] = $user['id'];
        }
        //设置排序，按照订单生成时间，降序
        val('_order', 'ad_id');
        val('_sort', 1);
    }


    /**
     * 显示之前处理数据
     * @author yangjia
     * @date 2014-11-14
     */
    protected function _data_filter(&$data, &$model) {
//        foreach ($data as &$value) {
//            $value['access_price'] = "￥".$value['access_price'];
//            $value['launch_price'] = "￥".$value['launch_price'];
//        }
    }

    /**
     * 产品分类编辑前置方法
     *
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:03:58
     */
    protected function _form_filter(&$model, &$data) {
        if (IS_GET) {
            /* 分类数据 */
            $map = array();
            $map['status'] = '2';
            //广告分类
            $adTypeList = M('AdType')->field('type_id,type_name')->where($map)->select();
            array_unshift($adTypeList, array('type_id' => 0, 'type_name' => '请选择广告类型'));
            $this->assign('adTypeList', $adTypeList);

            /* 广告主 */
            $advList = M('Advertiser')->field('advertiser_id,name')->where(array())->select();
            array_unshift($advList, array('advertiser_id' => 0, 'name' => '请选择广告主'));
            $this->assign('advList', $advList);

            /* 商务 */
            $user_map = array(
                'status' => 2,
                'role' => 22
            );
            $userList = M('SysUser')->field('id,name')->where($user_map)->select();
            array_unshift($userList,array('id' => 0,'name' => '请选择商务'));
            $this->assign('userList',$userList);
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
        if($data['id']) {
            //添加ad_sn  1 开头代表广告  2 开头代表广告主  3开头代表渠道
            $uuid = "1" . str_pad("{$data['id']}", 2, '0', STR_PAD_LEFT). date('ymdH') ;
            $this->_save(array('ad_sn' => $uuid), D('Ad'),array('ad_id' => $data['id']));
            if (!empty($data['advertiser_id'])) {
                $map = array();
                $map['status'] = 2;
                $map['advertiser_id'] = $data['advertiser_id'];
                M('Advertiser')->where($map)->setInc('product_num', 1);
            }
        }

    }

    /**
     * 追踪链接
     */
    public function track() {
        $ad_id = I('get.id');
        //获取广告信息
        $map = array(
            'ad_id' => $ad_id
        );
        $field = 'a.*,ar.advertiser_sn';
        $info = M("Ad")
            ->alias('a')
            ->field($field)
            ->join('left join __ADVERTISER__ ar on ar.advertiser_id=a.advertiser_id')
            ->where($map)
            ->find();
        $this->assign('info',$info);

        /* 渠道列表 */
        $channelList = M('Channel')->field('channel_id,channel_sn,name')->where(array('status' => 2))->select();
        $this->assign('channelList', $channelList);
        /* 网站配置 */
        $config = M('SiteConfig')->find();
        $this->assign('config',$config);
        if(IS_POST) {
            $data = array();
            $ad_id = I('post.ad_id');
            $track_link = I('post.track_link');
            $channel_id = I('post.channel_id');
            $data['track_link'] = $track_link;
            $data['channel_id'] = $channel_id;
            $result = M("Ad")->where(array('ad_id' => $ad_id))->save($data);
            if ($result) {
                if($info['channel_id']) {  //修改渠道
                    $desc = '渠道添加';
                    //修改渠道广告数量
                    $where = array();
                    $where['status'] = 2;
                    $where['channel_id'] = $channel_id;
                    if($info['channel_id'] != $channel_id) {
                        M('Channel')->where(array('status' => 2,'channel_id' => $info['channel_id']))->setDec('ad_num', 1);
                        M('Channel')->where(array('status' => 2,'channel_id' => $channel_id))->setInc('ad_num', 1);
                    }
                } else {
                    $desc = '渠道添加';
                    //修改渠道广告数量
                    $where = array();
                    $where['status'] = 2;
                    $where['channel_id'] = $channel_id;
                    M('Channel')->where($where)->setInc('ad_num', 1);
                }
                $this->success($desc."成功！");
            } else {
                $this->error("渠道添加失败！");
            }
        } else {
            $this->display();
        }
    }


    /**
     * 待定广告
     */
    public function examine() {
        $this->ptitle = "广告审核";
        $this->assign('ptitle', $this->ptitle);
        $this->_bind_model = 'Ad';
        parent::index();
    }


    protected function _examine_filter(&$model, &$map) {
        $map['ad_status'] = 0 ;
    }


    /**
     * 广告审核
     */
    public function to_examine() {
        $ad_id = $_GET['id'];
        $info['ad_status'] = 1 ;
        $res = M('Ad')->where(array('ad_id' => $ad_id))->save($info);
        if($res) {
            $this->success("审核通过");
        } else {
            $this->success("审核失败");
        }
    }

}
