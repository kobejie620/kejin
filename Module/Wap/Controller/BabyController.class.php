<?php

namespace Wap\Controller;

/**
 * 宝宝前台控制器
 * @author jindewen <jindewen@21cn.com>
 * @time 2015-03-24 18:00
 */
class BabyController extends WapController {

    protected $_bind_model = 'BabyDaily';

    /**
     * 用户信息检测
     */
    public function _initialize() {
        parent::_initialize();
        $this->getMember($this->oauth(), null, false);
    }

    /**
     * 宝宝日记
     */
    public function daily() {

        $id = I('get.id', false, 'intval');
        $mode = I('get.mode', 'self', 'trim');

        $offset = I('get.offset', 0, 'trim,intval');

        $map = array();
        if (!empty($id)) {
            $map['b.id'] = $id;
            $mode = 'view';
        } elseif ($mode === 'self') {
            $map['b.openid'] = $this->openid;
        } else if ($mode === 'all') {
            $map['b.openid|b.open'] = array($this->openid, '1', '_multi' => true);
        }
        $this->assign('mode', $mode);
        $list = M('BabyDaily')
                ->field('b.*,wm.headimgurl,wm.nickname')
                ->alias('b')
                ->where($map)
                ->join('left join __WECHAT_MEMBER__ wm ON wm.openid=b.openid')
                ->order('b.id DESC')
                ->limit($offset, $offset + 3)
                ->select();
        $ids = array_column($list, 'id');
        if (!empty($ids)) {
            $praise = $this->_get_praise_header($ids);
            $_praise = array();
            foreach ($praise as $vo) {
                isset($_praise[$vo['cid']]) || $_praise[$vo['cid']] = array();
                $_praise[$vo['cid']][] = $vo;
            }
            unset($praise);
            $index = $offset;
            foreach ($list as &$vo) {
                $vo['offset_url'] = url_filter(array('offset' => ++$index));
                $key = 'baby_praise_' . $vo['id'];
                if (!session($key)) {
                    M('BabyDaily')->where(array('id' => $vo['id']))->setInc('click', 1);
                    $vo['click'] ++;
                    session($key, 1);
                }
                $vo['is_praise'] = false;
                if (isset($_praise[$vo['id']])) {
                    $vo['praise'] = $_praise[$vo['id']];
                    $openids = array_column($_praise[$vo['id']], 'openid');
                    in_array($this->openid, $openids) && $vo['is_praise'] = true;
                } else {
                    $vo['praise'] = array();
                }
            }
            unset($_praise);
        }

        $this->assign('list', $list);

        if ($offset > 0 && IS_AJAX) {
            $this->display('list');
            die();
        }

        $this->assign('ptitle', '宝宝日志');
        $this->display();
    }

    /**
     * 宝宝日记添加
     */
    public function daily_handle() {
        $data = array(
            'openid'  => $this->oauth(),
            'sids'    => I('post.sids', '', 'trim'),
            'content' => I('post.content', '', 'trim'),
            'open'    => I('post.open', '0', 'intval')
        );
        $wechat = $this->getInstanceWechat();
        $links = array();
        $file_path = UPLOAD_PATH . 'daily/'; // 文件上传路径
        is_dir($file_path) || mkdir($file_path, 0777, true);
        foreach (explode(',', $data['sids']) as $media_id) {
            $img = $wechat->getMedia($media_id);
            $filename = str_replace(APP_ROOT, '', $file_path) . date('YmdHis') . rand(100, 999) . '.png';
            $file = fopen($filename, "w"); //打开文件准备写入
            fwrite($file, $img); //写入
            fclose($file); //关闭
            $links[] = $filename;
        }
        $data['images'] = implode(',', $links);
        M('BabyDaily')->add($data) ? $this->success('日记发布成功') : $this->error('日记发布失败，请稍后重试');
    }

    /**
     * 点赞处理
     */
    public function daily_praise() {
        $cid = I('post.id', 0, 'trim,intval');
        if (empty($cid)) {
            $this->error('操作失败，请稍候再试！');
        }
        $map = array();
        $map['openid'] = $this->openid;
        $map['cid'] = $cid;
        if (M('BabyDailyPraise')->where($map)->count() && false !== M('BabyDailyPraise')->where($map)->delete()) {
            $this->ajaxReturn(array('status' => 1, 'info' => '取消赞成功', 'praise' => $this->_get_praise_header(array($cid))));
        } elseif (false !== M('BabyDailyPraise')->add($map)) {
            $this->ajaxReturn(array('status' => 1, 'info' => '点赞成功', 'praise' => $this->_get_praise_header(array($cid))));
        } else {
            $this->error('操作失败，请稍候再试！');
        }
    }

    /**
     * 宝宝日志删除处理
     */
    public function daily_del() {
        $cid = I('post.id', 0, 'trim,intval');
        if (empty($cid)) {
            $this->error('操作失败，请稍候再试！');
        }
        $map = array();
        $map['openid'] = $this->openid;
        $map['cid'] = $cid;
        $info = M('BabyDaily')->where(array('id' => $cid))->find();
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

    /**
     * 获取点选头像列表
     * @param type $ids
     * @return type
     */
    protected function _get_praise_header($ids) {
        return M('BabyDailyPraise')
                        ->alias('p')
                        ->field('p.*,wm.headimgurl,wm.nickname')
                        ->where(array('cid' => array('in', $ids)))
                        ->join('left join __WECHAT_MEMBER__ wm on wm.openid=p.openid')
                        ->order('p.id desc')
                        ->select();
    }

}
