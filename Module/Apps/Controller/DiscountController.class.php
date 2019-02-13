<?php

namespace Apps\Controller;

use Library\Util\Excel;
use Think\Model;

/**
 * 
 *
 * @author zenglingwen <630118900@qq.com>
 * @date 2015-05-06
 */
class DiscountController extends AppsController {

    /**
     * 绑定控制器
     * @var type 
     */
    protected $_bind_model = 'Discount';

    /**
     * 定义模块代码
     * @var type 
     */
    public $ptitle = '门店提现';

    /**
     * 定义可访问的操作方法
     * @var type 
     */
    public $access = array(
        'index' => '门店折扣额度数据',
        'income' => '门店财务报表',
        'headquarters'=>'总部提现报表',
        'mendiantixian'=>'门店的提现报表',
        'refutemore'=>'驳回提现',
        'redemption'=>'发起提现',
        'passmore'=>'通过提现',
        'details'=>'订单详情',
    );

    /**
     * 定义可定义为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '门店折扣额度数据',
        'income' => '财务报表',
        'headquarters'=>'总部提现报表',
        'mendiantixian'=>'门店的提现报表',
    );

    public function index() {
        if (session('user.role') == '23') {
            $role = 1; /* 总店 */
        } elseif (session('user.role') == '24') {
            $role = 2; /* 门店 */
        } else {
            $role = 0; /* 管理员 */
        }
        $this->assign('role', $role);
        $this->assign('ptitle', '门店折扣额度数据');
        $this->_bind_model = 'Discount';
        $this->_callback_filter = '_record_filter';
        parent::index();
    }

    /* index前置过滤方法 */

    protected function _index_data_filter(&$data, &$model) {


    }

    /* index前置$map条件方法 */

    protected function _index_filter($model, &$map) {
 
        if(I("get.sid")){
        $id = I("get.sid");
        $map['sid'] = array('eq',$id);
        }elseif( I("get.status")){
         $map['status']=array('eq',I("get.status")); 
            
        }else{
            
          $map['status']=array('eq',3);   
        }
     if(session('user.role')==24){
    $map['sid']=array('eq',session('user.number'));
    }
    if(I('get.start_time') && I('get.end_time')){
        $a = strtotime(I('get.end_time')); 
       $map['create_time'] = array('between',array(I('get.start_time'),date('Y-m-d',$a+86400)));  
    }
    }

    /* 订单信息 */

    public function details() {
        $id = I("get.id");
        $info = M('StoreOrder')->where(array('id' => $id))->find();
        $product = M('StoreOrderProduct')->field('product_id')->where(array('order_id' => $id))->find();
        $name = M('StoreProduct')->field('name,discount')->where(array('id' => $product['product_id']))->find();
        $this->assign("name", $name['name']);
        $this->assign("discount", $name['discount']);
        $this->ptitle = "订单详情";
        $this->assign("ptitle", $this->ptitle);
        $this->assign("info", $info);
        $this->display();
    }

    /* 发起提现 */

    public function redemption() {
        $ids = I('post.ids', array());
        $result = M("Discount")->where(array("id" => array('in', $ids), "status" => array('eq', 1)))->select(); //只有状态为一才能发起提现
        $cofig = M("Store")->field('number,min_money')->find(); //读取商城的提现的配置
        $money = M("Discount")->where(array("id" => array('in', $ids), "status" => array('eq', 1)))->sum('price'); //获取申请的总钱数
        $num = M("Discount")->where( array("apply_time" => array('egt', date('Y-m-01', time())),"id" => array('in', $ids)) )->group('apply_time')->select(); //当月提交次数
        if($result != null) {
            if (count($num) >= $cofig['number']) {/* 如果超过限制弹出提示的信息 */
                $ajaxdata = array();
                $ajaxdata['status'] = 0;
                $ajaxdata['info'] = '当月提现的次数超过限制';
                $this->ajaxReturn($ajaxdata);
            }
            if ($money >= $cofig['min_money']) {//如果申请提现的金额超过设定的最低金额，则继续操作
                foreach ($result as $value) {
                    $apply_data = array();
                    $apply_data['status'] = 3;
                    $apply_data['apply_time'] = get_now_date();
                    M("Discount")->where(array("id" => array('eq', $value['id']), "status" => array('eq', 1)))->setField($apply_data);
                }
                $ajaxdata = array(); //操作成功返回提示
                $ajaxdata['status'] = 1;
                $ajaxdata['info'] = '申请成功，请耐心等待后台审核';
                $this->ajaxReturn($ajaxdata);
            } else {
                $ajaxdata = array();
                $ajaxdata['status'] = 0;
                $ajaxdata['info'] = '提现的金额低于最低额度';
                $this->ajaxReturn($ajaxdata);
            }
        } else {
            $ajaxdata['info'] = '请选择未提现的订单！';
            $this->ajaxReturn($ajaxdata);
        }
        
    }

    /* 驳回提现 */

    public function refutemore() {
        $ids = I('post.ids', array());
        $result = M("Discount")->where(array("id" => array('in', $ids), "status" => array('eq', 3)))->select();
        foreach ($result as $value) {
            M("Discount")->where(array("id" => array('eq', $value['id']), "status" => array('eq', 3)))->setField(array("status" => 4));
        }
        $this->success('成功');
    }

    /* 通过提现申请 */

    public function passmore() {
        $ids = I('post.ids', array());
        $result = M("Discount")->where(array("id" => array('in', $ids), "status" => array('eq', 3)))->select();
        foreach ($result as $value) {
            M("Discount")->where(array("id" => array('eq', $value['id']), "status" => array('eq', 3)))->setField(array("status" => 2));
        }
        $this->success('成功');
    }

    /* 每天的收入 */

    public function income() {
        /* 联合查询获取日期交集 */
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        if(empty($start_time)){
          $status_time = date('Y-m-01', time());   
        }else{
           $status_time =I('get.start_time') ;  
        }
       if(empty($end_time)){
         $use_time =date('Y-m-d', time());    
        }else{
           $use_time =I('get.end_time') ;  
        }
       

        $sql = M('StoreOrder')->field('SUBSTR(create_date,1,10) as create_date')
                ->alias('so')
                ->where(array('so.pay_status' => 1,'so.status'=>5,'so.send_status'=>2))
                ->buildSql();
       
        $date_sql = M('Discount')->field('SUBSTR(create_time,1,10) as create_date')
                         ->alias('discount')
                         ->where(array('discount.status' => 2))
                         ->group('create_date')
                        ->union($sql)->buildSql(); 
   
                        
        /* 收入、价格列处理 */
        $order_amount = M('StoreOrder')->field('sum(order_amount) as order_amount')
                ->where(array('SUBSTR(create_date,1,10)' => array('exp', '=d.create_date'),'pay_status' => 1,'status'=>5,'send_status'=>2))
                ->buildSql();
        $price = M('Discount')->field('sum(price) as price')
                ->where(array('SUBSTR(create_time,1,10)' => array('exp', '=d.create_date'),'status' => 2))
                ->buildSql();

        $list = M()->table($date_sql)->alias('d')->field(array(
                    $price => 'price',
                    $order_amount => 'order_amount',
                    'd.create_date'
                ))->where(array('d.create_date' => array('between', array($status_time, $use_time))))
                ->order('d.create_date asc')
                ->select();
        $shouru = 0;
        $zhichu = 0;
        foreach($list as $key=>$value){
            $shouru += $value['order_amount'];
            $zhichu += $value['price'];
            
            
        }
        /* 收入的数据 */
        $date = M('StoreOrder')->field('0 price,sum(order_amount)as order_amount,SUBSTR(create_date,1,10) as create_date')
                ->where(array('create_date' => array('between', array($status_time, $use_time)),'pay_status' => 1,'status'=>5,'send_status'=>2))
                ->group('SUBSTR(create_date,1,10)')->select();
        /* 支出的数据 */
        $date2 = M('Discount')->field('sum(price)as price,0 order_amount,SUBSTR(create_time,1,10) as create_date')
                        ->where(array('create_time' => array('between', array($status_time, $use_time)),'status' => 2))
                        ->group('SUBSTR(create_time,1,10)')->select();
        /* 数组合并 */
         /*dump($date);
          dump($date2); */
        $this->ptitle = "总店财务";
        $this->assign("shouru", $shouru);
        $this->assign("zhichu", $zhichu);
        $this->assign("ptitle", $this->ptitle);
        $this->assign('list', $list);
        $this->display();
    }
    
/*总部门店的提现申请记录*/
public function headquarters(){
    $data = array();   
    if(I('get.mdname')){ 
         $name =  '%'.I('get.mdname').'%';
         $data['name']=array('like',$name);}
    if(I('get.start_time') && I('get.end_time')){
         $data['apply_time'] = array('between',array(I('get.start_time'),I('get.end_time'))); 
    }    
     if( I('get.status')){
      $data['status'] = array('eq',I('get.status'));      
     }else{
      $data['status'] = array('eq',2);     
     }  


  $list =  M('Discount')->field('sum(price) as price,status,count(*) as num ,order_id ,sid,name,phone,apply_time')->where($data)->group('sid,status')->select();
  $this->assign('list', $list);
  $this->display();    
 
}
/*门店的提现申请记录*/
public function mendiantixian(){
    $data = array();
    if(session('user.role')==24){
    $data['sid']=array('eq',session('user.number'));
    }
     if(I('get.start_time') && I('get.end_time')){
     $data['create_time'] = array('between',array(I('get.start_time'),I('get.end_time'))); 
    }
    $data['status'] =array('neq','6');
  $list =  M('Discount')->field('sum(price) as price,status,count(*) as num ,order_id ,sid,name,phone,create_time')->where($data)->group('status')->select();
  $this->assign('list', $list);
  $this->display();    
 
}
}
