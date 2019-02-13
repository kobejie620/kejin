<?php

namespace Wechat\Controller;

use Wechat\Service\NewsService;

C('HANDLER_IMG_ERROR', false);

/**
 * 接口消息日志控制器
 * @author Anyon <zoujingli@qq.com>
 * @date 2014/08/21 19:43
 */
class NewsController extends WechatController {

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'WechatNews';

    /**
     * 设置模块标题
     */
    public $ptitle = '微信素材管理';

    /**
     * 设置模块可访问的节点
     * @var type 
     */
    public $access = array(
        'index'  => '素材列表',
        'add'    => '添加素材',
        'edit'   => '编辑素材',
        'del'    => '删除素材',
        'upload' => '上传素材',
        'send'   => '图文推送',
        'sync'   => '同步远程素材'
    );

    /**
     * 设置可配置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '素材列表',
    );

    /**
     * 微信操作
     * @var type 
     */
    protected $wechat;

    /**
     * 条件过滤
     * @param type $model
     * @param type $map
     */
    protected function _index_filter($model, &$map) {
        $map['is_delete'] = isset($map['is_delete']) ? $map['is_delete'] : '0';
        $map['type'] = isset($map['type']) ? $map['type'] : 'news';
    }

    /**
     * 列表显示前置方法
     */
    public function _before_index() {
        $action = I('get.action', '', 'trim');
        if (in_array($action, array('showWechatImage', 'getImageList', 'getMemberList', 'getMemberGroupList'))) {
            $service = new NewsService();
            $service->wechat = $this->getInstanceWechat();
            die($service->$action());
        }
    }

    /**
     * 推送图文消息
     */
    public function send() {
        $id = I('post.id', '', 'trim');
        $openids = I('post.member', '', 'trim');
        $groupids = I('post.group', false, 'trim,intval');
        $mode = I('post.mode', 'Preview', 'trim');
        ($mode !== 'All' && empty($openids) && empty($groupids)) && $this->error('请选择群发消息的微信会员。');

        (empty($id) || !($info = M($this->_bind_model)->find($id))) && $this->error('没有需要发送的图文，请检测后再试。');

        $service = new NewsService();
        $service->wechat = $this->getInstanceWechat();

        $result = $service->sendMassMsg($openids, $groupids, $info['media_id'], $mode);

        if ($result !== false) {
            $this->success('发送群组群发消息消息成功。');
        } else {
            $this->error('发送群组群发消息消息失败，' . $this->getInstanceWechat()->errMsg);
        }
    }

    /**
     * 素材上传
     */
    public function upload() {
        $url = I('post.url', '', 'trim');
        $num = 0;
        $result = true;
        $urls = explode('|', $url);
        foreach ($urls as $url) {
            $info = pathinfo($url);
            $filename = UPLOAD_PATH . 'news/image/' . date('Ym') . '/' . $info['basename'];
            !is_dir(dirname($filename)) && mkdir(dirname($filename), 0777, true);
            if (file_put_contents($filename, file_get_contents($url))) {
                $data = array();
                $data['title'] = $info['basename'];
                $data['media'] = "@{$filename}";
                $result = $this->getInstanceWechat()->uploadForeverMedia($data);
                unlink($filename);
                if ($result !== false) {
                    $result['type'] = 'image';
                    $result['local_update_time'] = time();
                    $result['name'] = $info['basename'];
                    $result = D($this->_bind_model)->add($result);
                }
                if (false === $result) {
                    break;
                }
                $num++;
            }
        }
        if ($result && $num) {
            $this->success('微信素材(' . $num . ')上传成功');
        } else {
            $this->error('微信素材上传失败，请稍候再试...');
        }
    }

    /**
     * 列表数据解析
     * @param type $data
     */
    protected function _index_data_filter(&$data) {
        foreach ($data as &$row) {
            if ($row['type'] === 'news') {
                $row['content'] = json_decode($row['content'], true);
            }
            $row['count'] = count($row['content']['news_item']);
        }
    }

    /**
     * 编辑数据处理
     * @param type $model
     * @param type $data
     */
    protected function _edit_filter($model, &$data) {
        if (!IS_POST) {
            if ($data['type'] === 'news') {
                $data['content'] = json_decode($data['content'], true);
            }
            $data['count'] = count($data['content']['news_item']);
        } else {
            $_POST['local_update_time'] = time();
        }
    }

    /**
     * 删除远程素材
     * @param type $model
     * @param type $ids
     */
    protected function _del_filter(&$model, &$ids) {
        $map = array();
        $map['id'] = array('in', $ids);
        $result = $model->where($map)->setField('is_delete', '1');
        if ($result !== false) {
            $this->success('标记删除成功，可在回收站中恢复。');
        } else {
            $this->error('标记删除失败，请稍候再试！');
        }
        return false;
    }

    /**
     * 同步远程素材
     */
    public function sync() {

        /**
         * setp 1. 同步本地删除的记录
         */
        $this->progress('开始同步本地删除的记录...');
        $this->_syncDelWechatNews();
        $this->progress('同步本地删除的记录完成');

        /**
         * step 2. 同步本地添加的记录
         */
        $this->progress('开始同步本地添加的记录...');
        $this->_syncAddWechatNews();
        $this->progress('同步本地添加的记录完成');
        /**
         * setp 3. 双向同步更新记录
         */
        $this->progress('开始双向同步修改的记录...');
        $this->_syncDifWechatNews();
        $this->progress('双向同步修改的记录完成');

        $this->progress('同步远程素材成功！', 1);
    }

    /**
     * 同步有差异的素材
     */
    protected function _syncDifWechatNews() {
        $countArr = $this->getInstanceWechat()->getForeverCount();
        if ($countArr === false) {
            $this->progress('同步远程素材失败，' . $this->getInstanceWechat()->errMsg, 0);
        }

        foreach ($countArr as $key => $count) {
            $offset = 0;
            while ($count > 0 && $offset <= $count - 1) {
                $type = strstr($key, '_', true);
                $list = $this->getInstanceWechat()->getForeverList($type, $offset, 20);
                if ($list === false) {
                    $this->progress('同步远程素材失败，' . $this->getInstanceWechat()->errMsg, 0);
                }
                $this->_syncSaveWechatNews($type, $list['item']);
                $offset+=20;
            }
        }
    }

    /**
     * 同步标记删除的素材
     */
    protected function _syncDelWechatNews() {
        $map = array();
        $map['type'] = array('in', array('news', 'image'));
        $map['is_delete'] = '1';
        $list = D($this->_bind_model)->where($map)->select();
        foreach ($list as $vo) {
            if (!empty($vo['media_id'])) {
                $result = $this->__delWechatNews($vo['media_id'], '2');
                if ($result === false) {
                    $this->progress('同步标记删除的数据时失败，' . $this->getInstanceWechat()->errMsg, 0);
                }
            }
        }
    }

    /**
     * 同步添加素材
     */
    protected function _syncAddWechatNews() {
        $map = array();
        $map['type'] = 'news';
        $map['is_delete'] = '0';
        $map['update_time'] = array(array('eq', '0'), array('exp', 'is null'), 'or');
        $list = D($this->_bind_model)->where($map)->select();
        foreach ($list as $row) {
            $this->__addWechatNews($row);
        }
    }

    /**
     * 保存远程素材
     * @param type $type
     * @param type $list
     */
    protected function _syncSaveWechatNews($type, $list) {
        foreach ($list as $row) {
            $row['type'] = $type;
            $map = array();
            $map['media_id'] = $row['media_id'];
            if ($type === 'news') {
                $local_data = D($this->_bind_model)->where($map)->find();
                if (!empty($local_data) && intval($local_data['local_update_time']) > intval($row['update_time'])) {
                    $content = json_decode($local_data['content'], true);
                    $articles = array(
                        'media_id' => $row['media_id'],
                        'articles' => $content['news_item'],
                        'index'    => $local_data['id'],
                    );
                    $result = $this->getInstanceWechat()->updateForeverArticles($row['media_id'], $articles);
                    if ($result === false) {
                        $this->progress('更新微信平台数据失败，' . $this->getInstanceWechat()->errMsg,0);
                    }
                    $row['local_update_time'] = $row['update_time'];
                    continue;
                } else {
                    $row['content'] = json_encode($row['content'], JSON_UNESCAPED_UNICODE);
                    $row['local_update_time'] = $row['update_time'];
                }
            } else {
                $row['name'] = pathinfo($row['name'], PATHINFO_BASENAME);
            }
            $this->_save($row, D('WechatNews'), $map);
        }
    }

    /**
     * 删除一条图文记录
     * @param type $media_id
     * @return type
     */
    protected function __delWechatNews($media_id, $is_delete = '2') {
        $result = $this->getInstanceWechat()->delForeverMedia($media_id);
        if (false !== $result) {
            $map = array();
            $map['media_id'] = $media_id;
            D($this->_bind_model)->where($map)->setField('is_delete', $is_delete);
        }
        return $result;
    }

    /**
     * 添加一条图文记录
     * @param type $row
     * @return type
     */
    protected function __addWechatNews($row) {
        $local_content = json_decode($row['content'], true);
        if (!empty($local_content['news_item'])) {
            $articles = array('articles' => $local_content['news_item']);
            $reslut = $this->getInstanceWechat()->uploadForeverArticles($articles);
            if ($reslut !== false && !empty($reslut['media_id'])) {
                $data = array();
                $data['id'] = $row['id'];
                $data['media_id'] = $reslut['media_id'];
                $data['update_time'] = $row['local_update_time'];
                return (false !== D($this->_bind_model)->save($data));
            }
        }
        return null;
    }

}
