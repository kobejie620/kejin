<?php

namespace Apps\Controller;

use Library\Util\Excel;
use Think\Model;

/**
 * 大转盘后台控制器
 *
 * @author tanglinjun <99439593@qq.com>
 * @date 2014-11-05
 */
class PromotionController extends AppsController {
/**
     * 绑定控制器
     * @var type 
     */
    protected $_bind_model = 'PromotionInfo';

    /**
     * 定义模块代码
     * @var type 
     */
    public $ptitle = '推广数据统计';

    /**
     * 定义可访问的操作方法
     * @var type 
     */
    public $access = array(
        'index'  => '推广数据统计',
        'promotionlist' => '推广员列表',
        'promotionnum' => '推广员数量',
        'score' => '成绩排行',
        'details' => '详情',
        'exchangerecord' => '兑换列表',
        'record'  => '推广用户数据',
    );

    /**
     * 定义可定义为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index'  => '推广数据统计',
        'score' => '成绩排行',
        'details' => '详情',
        'promotionnum' => '推广员数量',
        'promotionlist' => '推广员列表',
        'exchangerecord' => '兑换列表',
        'record'  => '推广用户数据',
    );
    
    public function index() {
        val('_order',use_time);
        val('_sort',1);
		$this->assign('ptitle', '推广数据统计');
        $this->_bind_model = 'PromotionInfo';
        $this->_callback_filter = '_record_filter';
        parent::index();
    }
    public function record() {
        val('_order',create_time);
        val('_sort',1);
        $this->assign('ptitle', '推广用户表格');
        $this->_bind_model = 'PromotionUser';
        $this->_callback_filter = '_record_filter';
        parent::index();
    }
    
    /**
     * 推广员列表
     */
    public function promotionlist() {
        val('_sort',1);
        $this->assign('ptitle', '推广员列表');
        $this->_bind_model = 'PromotionUser';
        $this->_callback_filter = '_record_filter';
        parent::index();
    }
	
    /*过滤塞选*/
	protected function _promotionlist_filter($model,&$map){
	   $phone = I('get.phone');
	   //电话过滤
	   if(!empty($phone)){
		  $info = M('Member')->where(array('phone'=>$phone))->find();
		  $map['promotion_id'] = array('eq',$info['id']); 
	   }
	   //导购员过滤
		$guide_name = I('get.guide_name');
	   if(!empty($guide_name)) {
		   $info = M('Guide')->where(array('guide_name' => $guide_name))->find();
		   $map['guide_id'] = array('in',$info['id']);
	   }
	} 
    
    protected function _promotionlist_list_filter(&$model) {       
        $model->field('pu.*,m.name,m.phone,m.sid,pi.status,count(pu.promotion_id) as promote_count,pi.stores , g.guide_name')
              ->alias('pu')
              ->where(array('pu.status' => 1))
              ->join('left join __MEMBER__ m on pu.promotion_id = m.id')
			  ->join('left join __GUIDE__ g on pu.guide_num = g.guide_num')
              ->join('left join __PROMOTION_INFO__ pi on pu.promotion_id = pi.uid')
              ->group('promotion_id');
    }
    
    protected function _promotionlist_data_filter(&$data, &$model) {
        //dump($data);
        foreach($data as &$val) {
            $val['account'] = M('PromotionUser')->where(array('promotion_id' => $val['promotion_id']))->count();
            $val['store_name'] = M('Stores')->field('name')->where(array('id' => $val['sid']))->find();
            if ($val['status'] == 2) {
                $val['number'] = 1 ;
            } else {
                $val['number'] = 0 ;
            }
        }
    }
    
    /**
     * 成绩排行
     */
    public function score() {
        val('_order','promote_count:force');
        val('_sort',1);
        $this->assign('ptitle', '成绩排行');
        $this->_bind_model = 'PromotionUser';
        $this->_callback_filter = '_record_filter';
        parent::index();
    }
	
	/*过滤塞选*/
	protected function _score_filter($model,&$map){
	   //导购员过滤
		$guide_name = I('get.guide_name');
	   if(!empty($guide_name)) {
		   $info = M('Guide')->where(array('guide_name' => $guide_name))->find();
		   $map['guide_id'] = array('in',$info['id']);
	   }
	} 
    
    /**
     * 成绩排行查询过滤
     * @param type $model
     */
    protected function _score_list_filter(&$model) {
        $model->field('pu.*,m.name,m.phone,pi.status,count(promotion_id) AS promote_count,ss.name as store_name , g.guide_name')
              ->alias('pu')
              ->join('left join __MEMBER__ m on pu.promotion_id = m.id')
			  ->join('left join __GUIDE__ g on pu.guide_num = g.guide_num')
              ->join('left join __PROMOTION_INFO__ pi on pu.promotion_id = pi.uid')
              ->join('left join __STORES__ ss on m.sid = ss.id')
              ->group('promotion_id');
    }
    
    /**
     * 成绩排行数据过滤
     * @param type $data
     * @param type $model
     */
    protected function _score_data_filter(&$data, &$model) {
        //dump($data);
        foreach($data as &$val) {
            $val['account'] = M('PromotionUser')->where(array('promotion_id' => $val['promotion_id']))->count();
            if ($val['status'] == 1) {
                $val['status'] = '未兑换';
                $val['number'] = 0 ;
            } elseif ($val['status'] == 2) {
                $val['status'] = '已兑换';
                $val['number'] = 1 ;
            } else {
                $val['status'] = '未达到兑换要求';
                $val['number'] = 0 ;
            }
        }
    }
    
    /**
     * 兑换列表
     */
    public function exchangerecord() {
        val('_order',create_date);
        val('_sort',1);
        $this->assign('ptitle', '兑换列表');
        $this->_bind_model = 'ExchangeRecord';
        $this->_callback_filter = '_record_filter';
        parent::index();
    }

    /**
     * 兑换列表查询过滤
     * @param type $model
     */
	protected function _exchangerecord_filter(&$model, &$map) {
		//电话过滤
		$phone = I('get.phone');
		if (!empty($phone)) {
			$info = M('Member')->where(array('phone' => $phone))->find();
			$map['openid'] = array('eq', $info['openid']);
		}
		//导购员过滤
		$guide_name = I('get.guide_name');
	   if(!empty($guide_name)) {
		   $info = M('Guide')->where(array('guide_name' => $guide_name))->find();
		   $map['guide_id'] = array('in',$info['id']);
	   }
	}

	protected function _exchangerecord_list_filter(&$model) {
        $model->field('er.*,ss.name as store_name , g.guide_name')
              ->alias('er')
				->join('left join __GUIDE__ g on er.guide_num = g.guide_num')
              ->join('left join __STORES__ ss on er.store_id = ss.id')
              ->where(array('result' => 1));
    }
    
    /**
     * 兑换列表数据过滤
     * @param type $data
     * @param type $model
     */
    protected function _exchangerecord_data_filter(&$data, &$model) {
        foreach($data as &$list) {
            $store_info = M('Stores')->where(array('number' => $list['store_id']))->find();
            $list['storename'] = $store_info['name'];
            $member_info = M('Member')->where(array('openid' => $list['openid']))->find();
            $list['name'] = $member_info['name'];
            $list['phone'] = $member_info['phone'];
        }
    }

    /*
     * 兑换列表
     */
//    public function exchangerecord() {
//        $this->ptitle = "兑换列表";
//        $info = M('ExchangeRecord')
//                ->field('er.*,ss.name as store_name')
//                ->alias('er')
//                ->join('left join __STORES__ ss on er.store_id = ss.id')
//                ->where(array('result' => 1))
//                ->order('create_date desc')
//                ->select();
//        foreach ($info as &$list) {
//            $store_info = M('Stores')->where(array('number' => $list['store_id']))->find();
//            $list['storename'] = $store_info['name'];
//            $member_info = M('Member')->where(array('openid' => $list['openid']))->find();
//            $list['name'] = $member_info['name'];
//            $list['phone'] = $member_info['phone'];
//        }
//        $this->assign("list",$info);
//        $this->assign("ptitle",$this->ptitle);
//        $this->display();
//    }
    
    /*
     * exchangerecord前置过滤
     */
    
    /*record前置过滤*/
    protected function _record_data_filter(&$data, &$model) {

        foreach ($data as &$val) {
            $user = M('Member')->where(array('id' => $val['promotion_id']))->find();
            //dump(M()->_sql());die;
            $val['promotion_id'] = $user['name'];
            $user = M('Member')->where(array('id' => $val['uid']))->find();
            $val['uid'] = $user['name'];
            if ($val['status'] == 1) {
                $val['status'] = '有效';
            }
        }
    }
    /*record前置过滤条件*/
     protected function _record_filter(&$model, &$map) {
       $map['status'] =1;      
     }
 /*index前置过滤*/
    protected function _index_data_filter(&$data, &$model) {

        foreach ($data as &$val) {
            $user = M('Member')->where(array('openid' => $val['openid']))->find();
            $store = M('Stores')->where(array('number' => $val['stores']))->find();
            //dump(M()->_sql());die;
            $val['uid'] = $user['name'];
             $val['stores'] = $store['name'];
            if ($val['status'] == 1) {
                $val['status'] = '有效';
            } elseif ($val['status'] == 2) {
                $val['status'] = '已兑换';
            } else {
                $val['status'] = '作废或未知状态';
            }
        }
    }
    
    /**
     * 推广员详情
     */
    public function details() {
        $id = I("get.id"); 
        $data = M('PromotionUser')->where(array('id' => $id))->find();
        $name = M('PromotionUser')
                ->field('m.name')
                ->alias('pu')
                ->where(array('pu.id' => $id))
                ->join('left join __MEMBER__ m on pu.promotion_id = m.id')
                ->find();
        $promotionname=implode("",$name);
        //$promotiondata = M('PromotionUser')->where(array('promotion_id' => $data['promotion_id']))->select();
        
        $info = M('PromotionUser')
                ->field('m.*,wm.nickname,wm.sex,wm.headimgurl')
                ->where(array('pu.promotion_id' => $data['promotion_id']))
                ->alias('pu')
                ->join('left join __MEMBER__ m on pu.uid = m.id')
                ->join('left join __WECHAT_MEMBER__ wm on pu.uid_openid = wm.openid')
                ->select();
//        $info = M('PromotionUser')
//                ->field('pu.promotion_id,m.*,wm.nickname,wm.sex,wm.headimgurl')
//                ->where(array('pu.id' => $id))
//                ->alias('pu')
//                ->join('left join __MEMBER__ m on pu.promotion_id = m.id')
//                ->join('left join __WECHAT_MEMBER__ wm on pu.promotion_openid = wm.openid')
//                ->select();
        foreach ($info as &$val) {
            $val['myaddress'] = $val['province'] . $val['city'] . $val['street'] . $val['address'] ;
        }
        $this->ptitle = "详情";
        $this->assign("promotionname",$promotionname);
        $this->assign("ptitle",$this->ptitle);
        $this->assign("info",$info);
        $this->display();
    }

    
    /*
     * 推广员数量
     */
    public function promotionnum() {
        $mydate = I('get.create_time');
        
        if($mydate === "") {
             $date = date("Y-m-d");
        } else {
            $date =$mydate ;
        }
        //$map["pu.create_time"] = array("like",$date . "%");
        $info = M('PromotionUser')
                ->field('pu.create_time,m.*,wm.nickname,wm.sex,count(promotion_id) num')
                ->alias('pu')
                ->join('left join __MEMBER__ m on pu.promotion_id = m.id')
                ->join('left join __WECHAT_MEMBER__ wm on pu.promotion_openid = wm.openid')
                ->where(array('pu.create_time'=>array('like', $date . "%")))
                ->group('promotion_id')
                ->select();
        foreach ($info as &$val) {
            $val['myaddress'] = $val['province'] . $val['city'] . $val['street'] . $val['address'] ;
        }
        
        //判断推广员数量
        $field = "count(distinct promotion_id) nums";
        $count = M('PromotionUser')->field($field)->find(); // 查询满足要求的总记录
        $total = intval($count['nums'], 10);
        
        //每天新增推广员数量
        $field1 = "count(distinct promotion_id) nums";
        $map["create_time"] = array("like",$date . "%");
        $count1 = M('PromotionUser')->field($field1)->where($map)->find(); // 查询满足要求的总记录
        $new_promotion = intval($count1['nums'], 10);
        
        $this->assign("list",$info);
        $this->assign("total",$total);
        $this->assign("new_promotion",$new_promotion);
        $this->assign("ptitle","推广员数量");
        $this->display();
    }
    
        /**
     * 推广员列表
     */
//    public function promotionlist() {
//        $this->ptitle = "推广员列表";
//        $info = M('PromotionUser')
//                ->field('pu.*,m.name,m.phone,pi.status')
//                ->alias('pu')
//                ->where(array('pu.status' => 1))
//                ->join('left join __MEMBER__ m on pu.promotion_id = m.id')
//                ->join('left join __PROMOTION_INFO__ pi on pu.promotion_id = pi.uid')
//                ->group('promotion_id')
//                ->select();
//        foreach($info as &$val) {
//            $val['account'] = M('PromotionUser')->where(array('promotion_id' => $val['promotion_id']))->count();
//            if ($val['status'] == 2) {
//                $val['number'] = 1 ;
//            } else {
//                $val['number'] = 0 ;
//            }
//            
//        }
//        $this->assign("list",$info);
//        $this->display();
//    }
    
        /**
     * 成绩排行
     */
//    public function score() {
//        $this->ptitle = "成绩排行";
//        $info = M('PromotionUser')
//                ->field('pu.*,m.name,m.phone,pi.status')
//                ->alias('pu')
//                ->join('left join __MEMBER__ m on pu.promotion_id = m.id')
//                ->join('left join __PROMOTION_INFO__ pi on pu.promotion_id = pi.uid')
//                ->group('promotion_id')
//                ->order("count('promotion_id') DESC")
//                ->select();
//        
//        foreach($info as &$val) {
//            $val['account'] = M('PromotionUser')->where(array('promotion_id' => $val['promotion_id']))->count();
//            if ($val['status'] == 1) {
//                $val['status'] = '未兑换';
//                $val['number'] = 0 ;
//            } elseif ($val['status'] == 2) {
//                $val['status'] = '已兑换';
//                $val['number'] = 1 ;
//            } else {
//                $val['status'] = '未达到兑换要求';
//                $val['number'] = 0 ;
//            }
//            
//        }
//        $this->assign("ptitle",$this->ptitle);
//        $this->assign("list",$info);
//        $this->display();
//    }
}
