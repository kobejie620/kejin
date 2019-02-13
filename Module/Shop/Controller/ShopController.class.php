<?php

namespace Shop\Controller;

use Library\Util\Express;
use Wap\Controller\WapController;

/**
 * WAP商城基础控制器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/22 10:53
 */
class ShopController extends WapController {

    public $ptitle = "微商城";

    /**
     * 商城配置信息
     * @var type 
     */
    protected $store_config = null;

    /**
     * 商品模块初始化方法
     */
    public function _initialize() {
        parent::_initialize();
        $this->getMember($this->oauth());
        $this->store_config = M('Store')->find();
        if (!empty($this->store_config['name'])) {
            $this->ptitle = $this->store_config['name'];
        }
    }

    /**
     * 物流查询接口
     */
    public function get_freight_detail($freight_num, $freight = '') {
        $express = new Express();
        $result = $express->query($freight_num);
        foreach ($result as $vo) {
            return $vo;
        }
    }

    /**
     * 异步获取快递信息
     */
    public function showFreight() {
        $num = I("post.freight_num");
        //判断物流单号是否存在
        if (!empty($num)) {
            // 获取物流详情
            $freight_detail = $this->get_freight_detail($num);
            $this->ajaxReturn($freight_detail);
        } else {
            $this->error("没有快递信息,尚未录入或者不存在... ...");
        }
    }
}
