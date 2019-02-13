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
class MemberController extends StoreController {

    public $ptitle = '会员管理';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index' => '会员列表',
    );
   /*index前置$map条件方法*/
    protected function _filter($model,&$map){
        if(I('get.storeid')){
             $info = M('Stores')->where(array('number'=>I('get.storeid')))->find();
            $map['sid'] = array('eq',$info['id']);
        }
       $map['province']=array('neq','');  
       $map['city']=array('neq','');  
    }
    /**
     * 定义可设置门店的节点
     * @var type 
     */
    public $menu = array(
        'index' => '会员列表',
    );

    /**
     * 显示之前处理数据
     * 
     * @author yangjia
     * @date 2014-11-14
     */
    protected function _data_filter(&$data, &$model) {
        foreach ($data as &$value) {
            $info = M('Stores')->where(array('id'=>$value['sid']))->find();
            $value['sid'] = $info['name'];
        }
       $info2 = M('Stores')->select();
     	$this->assign('info2', $info2); 
    }



    /**
     * 导入门店
     * 
     * @author yangjia
     * @date 2014-11-14
     */
 public function delmore() {
        $ids = I('post.ids', array());
        $info = M('Stores')->where(array('number'=>I('post.stare')))->find();
        $stare['sid'] = $info['id'];
        $result = M('Member')->where(array('id' => array('in', $ids)))->save($stare);
        $result ? $this->success('数据状态操作成功！') : $this->error($result . M()->_sql());
    } 

  
}
