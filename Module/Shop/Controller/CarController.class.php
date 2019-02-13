<?php

namespace Shop\Controller;

/**
 * 购物车控制器
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/22 10:51:47
 */
class CarController extends ShopController {

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'StoreOrderCar';

    /**
     * 绑定数据过滤规则
     * @var type 
     */
    protected $_bind_map = array('status' => 1);

    /**
     * 数据过滤操作
     * @param type $model
     * @param array $map
     */
    protected function _index_filter(&$model, &$map) {
        $map['openid'] = $this->openid;
    }

    /**
     * 列表数据过滤处理
     * @param type $data
     */
    protected function _index_data_filter(&$data) {
        $storeProduct=M('StoreProduct');
        $storeOrderCar=M('StoreOrderCar');
        //产品模型参数解析
        $productModel = D('Store/StoreProductModel');
        foreach ($data as $key => &$row) {
            $row['product'] = $storeProduct->find($row['cid']);
            //如果商品不存在，删除记录
            if (empty($row['product'])) {
                $storeOrderCar->delete($row['id']);
                unset($data[$key]);
            } else {
                
                $model_params = $row['product']['model_params'];
                $row['product']['model_params'] = $productModel->parseParams($model_params);
                $price=$storeOrderCar->field('price')->find($row['id']);
                $row['product']['sell_price']=$price?$price['price']:0;
            }
        }
    }

    /**
     * 添加商品到购物车
     * @author zoujingli <zoujingli@qq.com>
     * @modify Zoe(This is a good guy)
     * @date 2014/09/22 15:25:00
     */
    public function addcar() {
        $data = array();
        $data['openid'] = $this->openid;
        $data['cid'] = I('post.cid');
        $data['param'] = I('post.param');
        $data['num'] = I('post.num');
        $data['price'] = I('post.price');
        $data['status'] = 1;
        $map = array(
            'openid' => $data['openid'],
            'cid'    => $data['cid'],
            'param'  => $data['param'],
            'status' => 1
        ); 
        $model = D('StoreOrderCar');
        //检查是否存在记录
        if ($this->_isExistForDb($model, $data, $map)) {
            $result = $model->where($map)->setInc('num', $data['num']);
        } else {
            $result = $model->add($data);
        }
        $result !== false ? $this->success('添加到购物车成功！') : $this->error('添加到购物车失败，请稍候再试！');
    }

}
