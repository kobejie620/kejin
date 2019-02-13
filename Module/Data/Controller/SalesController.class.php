<?php

namespace Data\Controller;

use Library\Util\Api\Wechat;

class SalesController extends DataController {
    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'Stores';

    /**
     * 设置标题
     * @var type 
     */
    public $ptitle = '销售统计';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index'  => '销售统计',
        'details'=> '门店详情'
    );

    /**
     * 定义可设置门店的节点
     * @var type 
     */
    public $menu = array(
        'index' => '销售统计',
        'details'=> '门店详情'
    );
    /**
     * [_data_filter 数据过滤]
     * @param  [type] &$data [数据]
     * @param  [type] $model [数据模型对象]
     * @return [type]        [description]
     */
    protected function _data_filter(&$data, $model){
        $storeOrder=M('StoreOrder');
        $map['create_date']=array('like','%'.date('Y-m-d').'%');
        foreach ($data as $k => $v) {
            $map['store_id']=$v['id'];
            $data[$k]['order_count']=$storeOrder->where($map)->count();
            $data[$k]['order_sum']=$data[$k]['order_count'] >0 ? $storeOrder->where($map)->sum('order_amount'):0;
        }
    }
    /**
     * [index 统计页面]
     * @return [type] [description]
     */
    public function index(){
        $storeOrder=M('StoreOrder');
        $map['create_date']=array('like','%'.date('Y-m-d').'%');
        if($name=I('get.name','')){
            $storeId=M('Stores')->where(array('name'=>array('like',"%{$name}%")))->field('id')->select();
            $storeIdArr=array();
            foreach ($storeId as $v) {
                $storeIdArr[]=$v['id'];
            }
            if($storeIdArr)
            $map['store_id']=array('in',implode(',', $storeIdArr));
        }
        $orderCount=$storeOrder->where($map)->count();
        $orderSum=$orderCount >0 ? $storeOrder->where($map)->sum('order_amount'):0;
        $orderSum=sprintf('%.2f',$orderSum);
        $this->ptitle.=" - 今日订单总量：{$orderCount} 销售量：￥{$orderSum}";
        parent::index();
    }
    /**
     * [details 门店详情]
     * @return [type] [description]
     */
    public function details(){
        if(IS_AJAX){
            $storeOrder=M('StoreOrder');
            $map['store_id']=I('get.id','','intval');
            $map['create_date']=array('like','%'.date('Y-m-d').'%');

            $orderData=$storeOrder->where($map)->select();
            $data['info']=$orderData;
            $data['order_count']=count($orderData);
            $data['order_sum']=0;
            foreach ($orderData as $v) {
                $data['order_sum']+=$v['order_amount'];
            }
            $data['order_sum']=sprintf('%.2f',$data['order_sum']);
            $this->assign('vo', $data);
            $this->display('details');
            die;
        }
        $this->error('服务器繁忙，请稍候再试！ [404]',U('index'));
    }
}
