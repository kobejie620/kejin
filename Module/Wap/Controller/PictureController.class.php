<?php

/* * * * * * * * * * * * * * * * * * * * * *
 * * * * OH, NO BUG NO BUG, OH YEAH! * * * *
 * * * * * * * * * * * * * * * * * * * * * *
 */

namespace Wap\Controller;

/**
 * Description of PictureController
 *
 * @author luoshaobo <shao156324@sina.com>
 * @date 2015-5-6 18:14:37
 */
class PictureController extends WapController {

    public function _initialize() {
        parent::_initialize();
        $this->getMember($this->oauth(), null, true);
    }

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = '照片评比';

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'AppsVoteItem';

    /**
     * 列表过滤及附值
     * @param type $model
     * @param array $map
     */
    protected function _index_filter(&$model, &$map) {
        val('_sort', 1);
        val('_order', 'count');
        $vote = M("AppsVote")->where(array("status" => 2))->find();
        $map['vote_id'] = $vote['id'];
        $map['check'] = '2';
        $this->assign('vid', $vote['id']);
        $this->assign('title', $this->vote['title']);
        $this->assign('vote', $vote);
    }

    /**
     * 宝宝照片评选处理
     */
    public function handle() {
        if (IS_POST && IS_AJAX) {
            ($data['openid'] = $this->openid) || $this->error('请在微信里操作');

            $data['vid'] = I('post.vid');
            $data['pid'] = I('post.pid');
            (!$data['vid'] || !$data['pid']) && $this->error('参数错误');

            $info = M('AppsVote')->find($data['vid']); // 点赞规则

            /* 检查用户今天点赞次数是否超过限制 */
            $map = array(
                'openid'      => $data['openid'],
                'vid'         => $data['vid'],
                'create_time' => array('like', date('Y-m-d') . '%')
            );
            if (M('AppsVoteRecord')->where($map)->count() >= $info['praise_per_day']) {
                $this->error("每天只可以点赞{$info['praise_per_day']}次，请明天再来点赞");
            }

            /* 检查用户今天是否已经点赞过此选项 */
            $map['pid'] = $data['pid'];
            if (M('AppsVoteRecord')->where($map)->count()) {
                $this->error('今天已经赞过此照片了');
            }

            M()->startTrans();
            $result1 = M('AppsVoteRecord')->add($data);
            $result2 = M('AppsVoteItem')->where(array('id' => $data['pid']))->setInc('count', 1); // 点赞数+1
            if ($result1 === false || $result2 === false) {
                M()->rollback();
                $this->error('点赞失败，请稍候再试');
            }
            M()->commit();
            $this->success('点赞成功');
        }
    }

    public function join() {
        if (IS_POST) {
            //获取当前开启的活动
            $vote_id = M('AppsVote')->where(array("status" => 2))->getField("id");

            //判断是否已将参加过当前活动
            $openid = $this->openid;
            $info = M('AppsVoteItem')->where(array("openid" => $openid, "vote_id" => $vote_id))->find();
            if ($info) {
                $this->error("您已参加过该活动了！");
            }

            $data = array(
                'openid'  => $openid,
                'sids'    => I('post.sids', '', 'trim'),
                'title'   => I('post.content', '', 'trim'),
                'vote_id' => $vote_id,
                'status'  => 1,
                'check'   => 1
            );
            $wechat = $this->getInstanceWechat();
            $links = array();
            $file_path = UPLOAD_PATH . 'pic/'; // 文件上传路径
            is_dir($file_path) || mkdir($file_path, 0777, true);
            $ids = explode(',', $data['sids']);
            if (count($ids) > 1) {
                $this->error("最多只允许上传一张图片");
            }
            foreach ($ids as $media_id) {
                $img = $wechat->getMedia($media_id);
                $filename = str_replace(APP_ROOT, '', $file_path) . date('YmdHis') . rand(100, 999) . '.png';
                $file = fopen($filename, "w"); //打开文件准备写入
                fwrite($file, $img); //写入
                fclose($file); //关闭
                $links = to_domain($filename);
            }
            $data['link'] = $links;
            M('AppsVoteItem')->add($data) ? $this->success('参与成功，请等待审核！', U("Wap/Picture/index")) : $this->error('参与失败，请稍后重试');
        } else {
            $this->assign("ptitle", '上传图片');
            $this->display();
        }
    }

}
