<?php

namespace Apps\Controller;

/**
 * 宝宝日记控制器
 * @author jindewen <jindewen@21cn.com>
 * @date 2015/03/31 15:51:47
 */
class DailyController extends AppsController {

    /**
     * 绑定模型名
     * @var type
     */
    protected $_bind_model = 'BabyDaily';

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = "宝宝日记";

    /**
     * 指定模块可访问的节点
     * @var type 
     */
    public $access = array(
        'index' => '日志列表',
    );

    /**
     * 指定模块配置菜单可用的节点
     * @var type 
     */
    public $menu = array(
        'index' => '日志列表'
    );

    /**
     * 查询过滤处理
     * @param type $model
     * @param type $map
     */
    protected function _index_filter(&$model, &$map) {
        /* 查询条件 */
        ($kw = I("get._kw")) && $map['nickname|content'] = array("like", "%$kw%");
    }

    /**
     * 列表数据过滤处理
     * @param type $model
     */
    protected function _index_list_filter(&$model) {
        $model->field('a.*,b.nickname')->join('__WECHAT_MEMBER__ b ON a.openid = b.openid', 'LEFT')->alias('a');
    }

    /**
     * 删除成功的回调
     * @param type $model
     * @param type $data
     */
    public function del() {
        $cid = I('post.id', '0', 'trim');
        $ids = explode(',', $cid);
        if (empty($ids)) {
            $this->error('操作失败，请稍候再试！');
        }
        $map = array();
        $map['cid'] = array('in', $ids);
        $info = M('BabyDaily')->where(array('id' => array('in', $ids)))->find();
        if (M('BabyDailyPraise')->where($map)->delete() !== false && M('BabyDaily')->delete($cid) !== false) {
            $imgs = explode(',', $info['images']);
            foreach ($imgs as $img) {
                $file = APP_ROOT . $img;
                is_file($file) && unlink($file);
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败，请稍候再试！');
        }
    }

}
