<?php

namespace Member\Controller;

/**
 * 店铺管理控制器
 * 
 * @author chenrongbin
 * @date 2014/09/04 14:03:09
 */
class ConfigController extends MemberController {

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '兑换配置';

    /**
     * 设置可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '显示信息',
        'edit' => '配置参数',
    );

    /**
     * 设置可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '显示信息'
    );

    /**
     * 绑定操作模型
     * @var type 
     */

    protected $_bind_model = 'PrizePeizhi';


    /**
     * 显示兑换配置列表
     */
    public function index() {
//        $filename = APP_PATH . 'Member/Conf/exchange_config.php';
//        //$config = file_get_contents(__RES__ . '/conf/exchange_config.php');
//        $config = file_get_contents($filename);
//        $data = json_decode($config, true);
//        $this->assign('info', $data);
        $data = M('PrizePeizhi')->find();
        $this->assign('info',$data);
        $this->display();
    }

    /**
     * 更新兑换配置
     */
    public function set() {
        if(IS_POST){
            $data = I('post.');
            $info = array();
            $info['account'] = $data['account'];
            $info['prize_name'] = $data['prize_name'];
            $info['prize_img'] = $data['prize_img'];
            $info['cftitle'] = $data['cftitle'];
            $info['ftitle'] = $data['ftitle'];
            $info['fdesc'] = $data['fdesc'];
             $result = M('PrizePeizhi')->where(array('id' =>'1'))->save($info); 
             if($result){
                 $this->success('更新成功');
                 
             }else{
               $this->success('更新成功');   
                 
             }
        } 
//        $filename = APP_PATH . 'Member/Conf/exchange_config.php';
//        $con = I('post.');
//        $data = json_encode($con);
//        $result = file_put_contents($filename, $data);
        
    }

}
