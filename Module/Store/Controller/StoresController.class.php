<?php

namespace Store\Controller;

use Library\Util\Excel;
use Library\Util\Api\Wechat;

/**
 * 门店管理控制器
 *
 * @author tanglinjun
 * @date 2014-10-21
 */
class StoresController extends StoreController {

    public $ptitle = '门店管理';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index' => '门店列表',
        'extension' => '门店兑换',
        'exchange' => '确认兑换',
        'score' => '门店成绩',
        'storesrecord' => '门店兑换记录',
        'add' => '添加门店',
        'export' => '导入门店',
        'edit' => '编辑门店',
        'del' => '删除门店',
        'resume' => '启用门店',
        'forbid' => '禁用门店',
    );
   /*index前置$map条件方法*/
    protected function _filter($model,&$map){
        if (session('user.role') == '24') {//登录权限为门店
            $map['id'] = array('eq',session('user.number'));

        }
    }
    /**
     * 定义可设置门店的节点
     * @var type 
     */
    public $menu = array(
        'index' => '门店列表',
        'exchange' => '确认兑换',
        'extension' => '门店兑换',
        'storesrecord' => '门店兑换记录',
        'score' => '门店成绩',
    );

    /**
     * 显示之前处理数据
     * 
     * @author yangjia
     * @date 2014-11-14
     */
    protected function _data_filter(&$data, &$model) {
        foreach ($data as &$value) {
            $value = array_change_key_case($value, CASE_LOWER);
            $value['address'] = $value['street'];
           
        }
    }

    /**
     * 数据提交成功后的回调 生成带参数二维码
     * 
     * @author yangjia
     * @date 2015-05-11s
     */
    protected function _form_success($model, $data) {
        //调用Wechat类，
        $wechat_config = M('WechatConfig')->where(array('status' => 2))->find();
        $wechat = new Wechat($wechat_config);

        //1、创建二维码ticket
        $qrArr = $wechat->getQRCode($data['id'], 1);
        $ticket = $qrArr['ticket'];

        //2、获取二维码图片连接
        $qrUrl = $wechat->getQRUrl($ticket);
        $model->where(array("id" => $data['id']))->setField(array("qrcode" => $qrUrl));
       
    }

    /**
     * 导入门店
     * 
     * @author yangjia
     * @date 2014-11-14
     */
    public function export() {
                //调用Wechat类，
        $wechat_config = M('WechatConfig')->where(array('status' => 2))->find();
        $wechat = new Wechat($wechat_config);
        if (IS_POST) {
            set_time_limit(1800);
            $filename = UPLOAD_PATH . 'tmp/' . date('YmdHis') . rand(100, 999) . '.tmp';
//          mkdir(dirname(file), 0755, true);
            file_put_contents($filename, file_get_contents(I('post.myfile')));
            $excel = new Excel();
            $data = $excel->readerExcel($filename);
            foreach ($data as $key => $value) {
                $address = $value['4'] . $value['5'] . $value['6'];
                $latLng = json_decode(file_get_contents("http://apis.map.qq.com/ws/geocoder/v1?region=" . $value['5'] . "&address=" . $address . "&key=WZNBZ-IESWU-JUXV6-4KLHN-KBCD3-WUFJD"), true);
                $data[$key][7] = $latLng['result']['location']['lat'] . ',' . $latLng['result']['location']['lng'];
            }
            foreach ($data as $k => $v) {
                if (empty($v[0])) {  //如果门店名称为空则结束本次循环
                    continue;
                }
                $ExamObj = array(
                    'name' => $v[0],
                    'number' => $v[1],
                    'username' => $v[2],
                    'phone' => $v[3],
                    'province' => $v[4],
                    'city' => $v[5],
                    'street' => $v[6],
                    'latLng' => $v[7],
                );
                $stores = D("Stores");
                $stores->create($ExamObj);
                $id = $stores->add();
                    //1、创建二维码ticket
        $qrArr = $wechat->getQRCode($id, 1);
        $ticket = $qrArr['ticket'];
        //2、获取二维码图片连接
        $qrUrl = $wechat->getQRUrl($ticket);
        M("Stores")->where(array("id" =>$id))->setField(array("qrcode" => $qrUrl));    
            }
            unlink($filename); //删除
            $this->success("店铺导入成功");
        } else {
            $this->display();
        }
    }  
 public function delmore() {
        $ids = I('post.ids', array());
        $result = M('Stores')->where(array('id' => array('in', $ids)))->delete();
        $result ? $this->success('数据状态操作成功！') : $this->error($result . M()->_sql());
    } 
    
    /**
     * 线下门店
     * @author chenrongbin
     */
    public function extension() {
        if(IS_POST) {
            $user = session('user');
            $store_id = $user['number'];
//            $stores_info = M('Stores')->where(array('id' => $user['number']))->find();
            $code = I('post.code');
//            $store_id = I('post.store_id');

            $info = M('PromotionInfo')->where(array('cdkey' => $code))->find();
            $num = M('ExchangeRecord')->where(array('openid' => $info['openid']))->find();
//            $number = M('Stores')->where(array('number' => $store_id))->find(); 
            if($info != null) {
//                if(trim($stores_info['number']) === trim($store_id)) {
                if($num != "") {
                    $this->error("该推广员已经兑换过了!");                
                } else {
                    $url = U('Store/Stores/exchange', array('code' => $info['cdkey'],'store_id' => $store_id));
                    M("PromotionInfo")->where(array("cdkey" => $code, "status" => 1))->find() ? $this->success("正在处理！",$url) : $this->error("您输入的兑换码已被使用!");           
                }
                    
//                } else {
//                    $this->error("请输入该门店的门店编号!");
//                }
               
            } else {
                $this->error("兑换码错误!");
            }
            
        }else {
            $this->assign("ptitle","门店兑换");
            $this->display();  
        }
    }
    
    /**
     * 确认兑换
     * @author chenrongbin
     */
    public function exchange() {   
        if(IS_POST) {
            $code = I('post.code');
            $store_id = I('post.store_id');
            $store_info = M('Stores')->where(array('id' => $store_id))->find();
            
            //获取奖品信息
            $data = M('PrizePeizhi')->find();
         
            //获取兑换码信息
            $myinfo = M('PromotionInfo')
                      ->field('pi.*,m.name,m.openid')
                      ->alias('pi')
                      ->where(array("cdkey" => $code))
                      ->join('left join __MEMBER__ m on pi.uid = m.id')
                      ->find();
            $info = array();   
            $info['code'] = $code;
            $info['store_id'] = $store_info['id'];
            $info['username'] = $myinfo['name'];
            $info['openid'] = $myinfo['openid'];
            $info['store_name'] = $store_info['name'];
            $info['prize_name'] = $data['prize_name'] ;
            $info['prize_img'] = $data['prize_img'] ;
            $info['create_date'] = date("Y-m-d H-i:s");
            $info['result'] = 1;

            $str = array();
            $str['status'] = 2 ;
            $str['stores'] = $store_id ;
            $str['use_time'] = date("Y-m-d H-i:s");
            
            $url = U('Store/Stores/storesrecord');
            M('PromotionInfo')->where(array('cdkey' => $code))->save($str) && M("ExchangeRecord")->data($info)->add() ? $this->success("兑换成功！" ,$url) : $this->error("兑换失败！");
        } else {
            $code = I('get.code');
            $store_id = I('get.store_id');
            //获取奖品信息
            $info = M('PrizePeizhi')->find();
            
            $this->assign("code",$code);
            $this->assign("info",$info);
            $this->assign("ptitle","确认兑换");
            $this->assign("store_id",$store_id);
            $this->display();
        }
    }
    
    /**
     * 门店兑换记录
     * @author chenrongbin
     */
    public function storesrecord() {
        //获取该门店的信息
        $user = session('user'); 
        $store_id = I("get.store_id"); 
        $map['result'] = 1;
        if($store_id != "") {       /* 详情页面 */
            $user['role'] = '24' ;  //門店
            $user['number'] = $store_id;
        }
        //总店的兑换记录
        if ($user['role'] == '23') {
            $role = 1; /* 总店 */
           
            //获取所有门店的兑换记录
            $info = M('ExchangeRecord')
                    ->field('er.store_id,ss.name , er.prize_name ')
                    ->alias('er')
                    ->where($map)
                    ->group('er.store_id')
                    ->join('left join __STORES__ ss on er.store_id = ss.id')
                    ->select();
            //该门店粉丝总数
            foreach($info as &$val) {
                $val['duihuan_total'] = M('ExchangeRecord')
                                    ->field('count(store_id) duihuan_total ')
                                    ->where(array('store_id' => $val['store_id']))
                                    ->find();
                $val['duihuan_total'] = intval($val['duihuan_total']['duihuan_total'], 10);
            }
        }
        //详情
        elseif ($user['role'] == '25') {
            
        }
        //门店的兑换记录
        elseif ($user['role'] == '24') {  
           $role = 2; /* 门店 */
           $map['store_id'] = $user['number'];
           $info = M('ExchangeRecord')
                ->field('er.create_date,er.username,m.phone,er.prize_name')
                ->alias('er')
                ->join('left join __MEMBER__ m on er.openid = m.openid')
                ->where($map)
                ->order("er.create_date DESC")
                ->select();
        } else {
            $role = 0; /* 管理员 */
            
            //获取所有门店的兑换记录
            $info = M('ExchangeRecord')
                    ->field('er.store_id,ss.name , er.prize_name ')
                    ->alias('er')
                    ->where($map)
                    ->group('er.store_id')
                    ->join('left join __STORES__ ss on er.store_id = ss.id')
                    ->select();
            //该门店粉丝总数
            foreach($info as &$val) {
                $val['duihuan_total'] = M('ExchangeRecord')
                                    ->field('count(store_id) duihuan_total ')
                                    ->where(array('store_id' => $val['store_id']))
                                    ->find();
                $val['duihuan_total'] = intval($val['duihuan_total']['duihuan_total'], 10);
            }
        }

        //获取改门店兑换记录信息
        $this->assign("ptitle","门店兑换记录");
        $this->assign('role', $role);
        $this->assign("list",$info);
        $this->display();
    }


    /*
     * 门店成绩
     * @author chenrongbin
     */
    public function score() {
        $user = session('user'); 
        $sid = I("get.sid"); 
        $date = date("Y-m-d");
        $month = date("Y-m");
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        if($sid != "") {   //詳情
            $user['role'] = '25' ;  /* 门店 */
            $user['number'] = $sid;
        }
        if ($user['role'] == '23') {
            $role = 1; /* 总店 */
            
            //获取查询时间
            //获取查询门店名称
            $name = I('get.name');
            $map['m.sid'] = array("neq","");
            //门店名称查询
            if($name != "") {
                 $map["ss.name"] = array("like", "%" . $name . "%"); 
            }
            //获取所有门店的成绩
            $info = M('Member')
                    ->field('m.sid,ss.name')
                    ->alias('m')
                    ->where($map)
                    ->group('m.sid')
                    ->join('left join __STORES__ ss on m.sid = ss.id')
                    ->select();
            foreach($info as &$val) {
                //该门店粉丝总数
                $val['total_num'] = M('Member')
                                    ->field('count(openid) total_num')
                                    ->where(array('sid' => $val['sid']))
                                    ->find(); 
                $val['total_num'] = intval($val['total_num']['total_num'], 10);
                //今天新增粉丝总数
                $val['new_num'] = M('Member')
                                  ->field('count(openid) new_num')
                                  ->where(array('sid' => $val['sid'] ,'create_date' => array('like',"%" . $date . "%")))
                                  ->find();
                $val['new_num'] = intval($val['new_num']['new_num'], 10);
                //当月新增粉丝总数
                $val['month_new_num'] = M('Member')
                                        ->field('count(openid) month_new_num')
                                        ->where(array('create_date' => array('like',$month . "%"),'sid' => $val['sid']))
                                        ->find();
                $val['month_new_num'] = intval($val['month_new_num']['month_new_num'], 10);
            }
            $ptitle = "门店成绩";
        }elseif ($user['role'] == '25') {     //详情
            $role = 3;
            $info = M('Member')
                ->field('m.*,wm.nickname,wm.sex,wm.headimgurl')
                ->where(array('m.sid' => $user['number']))
                ->alias('m')
                ->join('left join __WECHAT_MEMBER__ wm on m.openid = wm.openid')
                ->select();
            foreach($info as &$val) {
                $val['myaddress'] = $val['province'] . $val['city'] . $val['street'] .$val['address'];
            }
            $ptitle = "详情";
        }elseif ($user['role'] == '24') {
            $role = 2; /* 门店 */
            //获取该门店的信息
            
            if(($start_time != "") || ($end_time != "")) {
                if($end_time === "") {
                    $end_time = date("Y-m-d");
                }
                $map['m.create_date'] = array('between',"$start_time,$end_time");
                $map['m.sid'] = $user['number'] ;
            } else {
                $map['m.sid'] = $user['number'] ;
            }    
            $info = M('Member')
                    ->field('count(openid) new_num , m.create_date')
                    ->alias('m')
                    ->where($map)
                    ->group('m.openid')
                    ->order("m.create_date DESC")
                    ->select();

            foreach ($info as &$val) {
                $val['create_date'] = substr($val['create_date'],0,10);
            }

            //目前粉丝总数
            $nums = M('Member')
                        ->field('count(openid) num')
                        ->alias('m')
                        ->where(array('m.sid' => $user['number']))
                        ->find();
            $total_num = intval($nums['num'], 10);

            //每月累计粉丝数
            $month_info = M('Member')
                    ->field('count(openid) month_num')
                    ->alias('m')
                    ->where(array('m.create_date' => array('like',$month . "%"),'m.sid' => $user['number']))
                    ->find();
            $month_num = intval($month_info['month_num'], 10);
            $ptitle = "门店成绩";
            $this->assign("total_num",$total_num);
            $this->assign("month_num",$month_num);
        } else {
            $role = 0; /* 管理员 */
            
            //获取查询时间
            //获取查询门店名称
            $name = I('get.name');

            $map['m.sid'] = array("neq","");
            if($name != "") {
                 $map["ss.name"] = array("like", "%" . $name . "%"); 
            }
            //获取所有门店的成绩
            $info = M('Member')
                    ->field('m.sid,ss.name')
                    ->alias('m')
                    ->where($map)
                    ->group('m.sid')
                    ->join('left join __STORES__ ss on m.sid = ss.id')
                    ->select();
            //该门店粉丝总数
            foreach($info as &$val) {
                $val['total_num'] = M('Member')
                                    ->field('count(openid) total_num')
                                    ->where(array('sid' => $val['sid']))
                                    ->find();
                $val['total_num'] = intval($val['total_num']['total_num'], 10);
                //今天新增粉丝总数
                $val['new_num'] = M('Member')
                                  ->field('count(openid) new_num')
                                  ->where(array('sid' => $val['sid'] ,'create_date' => array('like',"%" . $date . "%")))
                                  ->find();
                $val['new_num'] = intval($val['new_num']['new_num'], 10);  
            }
            $ptitle = "门店成绩";
        }
        $this->assign("list",$info);
        $this->assign('role', $role);
        $this->assign("ptitle",$ptitle);
        $this->display();
    }
    
}
