<?php

namespace Wap\Controller;

/**
 * 分享有礼控制器
 * @author jindewen <jindewen@21cn.com>
 * @time 2015-03-24 18:00
 */
class ShareController extends WapController {

    public function _initialize() {
        parent::_initialize();
        $this->oauth();
    }

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'AppsShare';

    /**
     * 首页列表语句拼装
     */
    protected function _index_list_filter(&$model, &$map) {
        $map = array("status" => 2);
        $model->field("id,title,content,link")->where($map); // 获取分类
    }

    /**
     * 列表数据
     * @param type $data
     */
    protected function _index_data_filter(&$data) {
        
    }

    /**
     * 详情
     */
    public function detail() {
        $article = M("AppsShare")->find(I("get.id", 0, "intval"));
        $this->assign("vo", $article);
        
        $this->inviter = $this->openid;
        
        /* 给分享文章的会员加积分 */
        $model = D('AppsViewRecord');
        $inviter = decode(I('inviter'));
        if (!empty($inviter) && $model->where(array('inviter' => $inviter, 'openid' => $this->openid, 'article_id' => $article['id']))->count() == 0) {
            change_member_integral($article['view_integral'], '文章被浏览', $inviter); // 给分享会员加分
            $model->add(array(
                'openid' =>  $this->openid,
                'article_id' => $article['id'],
                'inviter' => $inviter
            )); // 添加浏览记录
        }

        $this->display();
    }

    /**
     * 分享处理
     */
    public function handle() {
        if (IS_POST && IS_AJAX) {
            $openid = $this->openid;
            $share = M('AppsShare')->find(I('id'));

            // 生成记录
            M('AppsShareRecord')->add(array(
                        'article_id' => $share['id'],
                        'integral' => $share['share_integral'],
                        'openid' => $openid
                    )) || $this->error('系统繁忙，请稍候再分享');

            /* 给会员加积分 */
            change_member_integral($share['share_integral'], '分享文章');

            $this->success("分享文章，获取{$share['share_integral']}积分");
        }
    }

}
