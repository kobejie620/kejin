<?php

namespace Apps\Controller;

/**
 * 照片评比控制器
 * 
 * @author jindewen <jindewen@21cn.com>
 * @date 2015/03/25 15:51:47
 */
class CompareController extends AppsController {

    /**
     * 定义模型名称
     * @var type 
     */
    public $ptitle = '照片评比';

    /**
     * 设定可访问的操作
     * @var type 
     */
    public $access = array(
        'index'  => '评比列表',
        'add'    => '添加选项',
        'edit'   => '修改选项',
        'del'    => '删除选项',
        'resume' => '启用',
        'forbid' => '禁用',
    );

    /**
     * 设定可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '评比列表'
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'AppsVoteItem';

    protected function _filter(&$model, &$map) {
        ($kw = I('get._kw')) && $map['vi.title'] = array('like', "%$kw%");
        ($check = I('get.check')) && $map['vi.check'] = $check;
        ($vote_id = I('get.vote_id')) && $map['vi.vote_id'] = $vote_id;

        $this->assign('cats', M('AppsVote')->order('id desc')->select());
    }

    protected function _list_filter(&$model) {
        $model->field("vi.*,vo.title as act_title")
                ->join("left join __APPS_VOTE__ vo on vo.id=vi.vote_id")
                ->alias('vi');
    }

    protected function _form_filter(&$model, &$map) {
        $this->activity = M("AppsVote")->where(array("status" => 2))->find();
    }

    /**
     * 点赞列表
     */
    public function detail() {
        $map = array();
        $map['pid'] = I('get.id');
        $this->assign('ptitle', '点选统计');
        $list = M("AppsVoteRecord")
                ->alias('a')
                ->field('a.id,a.create_time,b.nickname')
                ->join('left join __WECHAT_MEMBER__ b ON a.openid = b.openid')
                ->where($map)
                ->order('a.id desc')
                ->select();
        $this->assign('list', $list);
        $this->display();
    }

    public function detail_del() {
        $this->_bind_model = 'AppsVoteRecord';
        parent::del();
    }

}
