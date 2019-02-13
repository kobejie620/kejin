<?php

namespace Wechat\Service;

use Library\Util\Api\Wechat;
use Think\Controller;

/**
 * 公众号图文处理服务层
 */
class NewsService extends Controller {

    /**
     * 微信SDK操作
     * @var Wechat 
     */
    public $wechat = null;

    /**
     * 输出素材文件
     */
    public function showWechatImage() {
        $media_id = I('get.media_id', '', 'trim');
        if ($media_id === 'none') {
            redirect(__ROOT__ . '/Static/Resoure/img/image.png');
        }
        if (empty($media_id)) {
            redirect(__ROOT__ . '/Static/Resoure/img/none-img.png');
        }
        $key = 'media_' . $media_id;
        $result = F($key);
        if (empty($result)) {
            $result = $this->wechat->getForeverMedia($media_id);
            F($key, $result);
        }
        ob_flush();
        header("Content-type: image/jpg");
        echo $result;
        flush();
    }

    /**
     * 获取图片素材列表
     */
    public function getImageList() {
        $map = array();
        $map['type'] = 'image';
        $list = D('WechatNews')->where($map)->select();
        $this->ajaxReturn($list);
    }

    /**
     * 获取会员列表
     */
    public function getMemberList() {
        $map = array();
        $map['status'] = '2';
        $list = D("WechatMember")->where($map)->field('id,nickname,headimgurl,openid,group_id')->limit(0, 500)->select();
        $this->ajaxReturn($list);
    }

    /**
     * 获取会员分组列表
     */
    public function getMemberGroupList() {
        $map = array();
        $map['status'] = '2';
        $list = D("WechatMemberGroup")->where($map)->select();
        $this->ajaxReturn($list);
    }

    /**
     * 推送消息给用户
     * @param type $openids 指定接收者OPENID
     * @param type $groupids 指定接收者所在分组GROUPID
     * @param type $media_id 微微信服务器上的图文MEDIAID
     * @param type $mode 发送模式 Mass|Group|Preview
     * @return type
     */
    public function sendMassMsg($openids, $groupids, $media_id, $mode = 'Preview') {
        switch (strtolower($mode)) {
            case 'all':
                $groupids = M('WechatMemberGroup')->getField('id', true);
            case 'group':
                $result1 = true;
                $result2 = true;
                if (!empty($openids) && is_array($openids)) {
                    $openids = array_unique($openids);
                    $result1 = $this->_sendMsgByPreview($openids, $media_id);
                }
                if (!empty($groupids) && is_array($groupids)) {
                    $groupids = array_unique($groupids);
                    $result2 = $this->_sendMsgByGroup($groupids, $media_id);
                }
                return ($result1 && $result2);
            case 'mass':
            case 'preview';
            default :
                $method = "_sendMsgBy{$mode}";
                if (!empty($groupids) && is_array($groupids)) {
                    $map = array();
                    $map['group_id'] = array('in', $groupids);
                    $_openids = M('WechatMember')->where($map)->getField('openid', true);
                    $openids = array_merge((array) $openids, (array) $_openids);
                }
                $openids = array_unique($openids);
                return $this->$method($openids, $media_id);
        }
        return null;
    }

    /**
     * 分组群发消息给指定GROUPID
     * @param array $groupids
     * @param type $media_id
     * @return boolean
     */
    protected function _sendMsgByGroup(array $groupids, $media_id) {
        $data = array();
        $data['filter'] = array();
        $data['filter']['is_to_all'] = false;
        $data['filter']['group_id'] = $groupids;
        $data['msgtype'] = 'mpnews';
        $data['mpnews'] = array("media_id" => $media_id);
        p('======= send group messge ' . date('Y/m/d H:i:s') . '=====');
        $result = $this->wechat->sendGroupMassMessage($data);
        if ($result === false) {
            p('Error:' . $this->wechat->errMsg);
            return false;
        }
        return true;
    }

    /**
     * 群发消息给指定的OPENID
     * (适合发送少量推送用户)
     * 
     * @param array $openids
     * @param type $media_id
     * @return boolean
     */
    protected function _sendMsgByMass(array $openids, $media_id) {
        foreach ($openids as $key => $openid) {
            if (empty($openid)) {
                unset($openids[$key]);
            }
        }
        if (count($openids) < 2) {
            return $this->_sendMsgByPreview($openids, $media_id);
        }

        $data = array();
        $data['touser'] = $openids;
        $data['msgtype'] = 'mpnews';
        $data['mpnews'] = array("media_id" => $media_id);
        p('======= send openid messge ' . date('Y/m/d H:i:s') . '=====');
        $result = $this->wechat->sendMassMessage($data);
        if ($result === false) {
            p('Error:' . $this->wechat->errMsg);
            return false;
        }
        return true;
    }

    /**
     * 通过消息预览发送给指定的OPENID
     * (适合发送少量推送用户)
     * 
     * @param array $openids
     * @param type $media_id
     * @return boolean
     */
    protected function _sendMsgByPreview(array $openids, $media_id) {
        foreach ($openids as $openid) {
            if (empty($openid)) {
                continue;
            }
            $data = array();
            $data['touser'] = $openid;
            $data['msgtype'] = 'mpnews';
            $data['mpnews'] = array("media_id" => $media_id);
            p('======= send preview messge ' . date('Y/m/d H:i:s') . '=====');
            $result = $this->wechat->previewMassMessage($data);
            if ($result === false) {
                p('Error:' . $this->wechat->errMsg);
                return false;
            }
        }
        return true;
    }

}
