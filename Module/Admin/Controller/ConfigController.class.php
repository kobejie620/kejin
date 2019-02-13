<?php

namespace Admin\Controller;

/**
 * 系统配置
 */
class ConfigController extends AdminController {

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'SysConfig';

    /**
     * 模块标题
     * @var type 
     */
    public $ptitle = '系统参数';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index' => '显示信息',
        'edit'  => '配置参数',
    );

    /**
     * 定义可访问的菜单节点
     * @var type 
     */
    public $menu = array(
        'index' => '显示信息',
    );

    /**
     * 显示系统配置列表
     */
    public function index() {
        $model = M('SysConfig');
        if (IS_POST) {
            $data = array();
            $model->startTrans();
            foreach (I('post.') as $key => $vo) {
                if (empty($key)) {
                    continue;
                }
                $data = array('value' => $vo);
                $result = $this->_save($data, $model, array('code' => $key));
                if ($result['status'] === 0) {
                    $model->rollback();
                    $this->ajaxReturn($result);
                }
            }
            S('sysconfig', null);
            $this->ajaxReturn($result);
        } else {
            $_list = $model->order('sort asc,id asc')->select();
            $list = array();
            foreach ($_list as $value) {
                $list[$value['type']][] = $value;
            }
            unset($_list);
            $this->assign('ptitle', $this->ptitle);
            $this->assign('list', $list);
            $this->display();
        }
    }

}
