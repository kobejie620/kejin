<?php

namespace Wechat\Controller;

/**
 * 接口消息日志控制器
 * @author Anyon <zoujingli@qq.com>
 * @date 2014/08/21 19:43
 */
class MsgController extends WechatController {

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'WechatMsg';

    /**
     * 模块标题
     * @var type 
     */
    public $ptitle = '微信消息管理';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index' => '消息列表',
        'edit'  => '消息回复',
        'del'   => '删除记录',
    );

    /**
     * 定义可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '消息列表',
    );

    /**
     * 消息类型
     * 
     * @var type 
     */
    protected $msgType = array(
        'event'              => '事件',
        'location'           => '手动位置',
        'text'               => '文本消息',
        'image'              => '图片消息',
        'scancode_waitmsg'   => '菜单扫码带提示',
        'scancode_push'      => '菜单扫码推事件',
        'pic_sysphoto'       => '系统拍照发图',
        'pic_photo_or_album' => '拍照或者相册发图',
        'pic_weixin'         => '微信相册发图',
        'location_select'    => '菜单发送位置',
    );

    /**
     * 首页列表前置方法
     * 1、改变列表默认排序 
     */
    public function _before_index() {
        val('_sort', 'desc');
    }

    /**
     * 只取当前微信的消息记录
     * @param array $map
     */
    protected function _index_filter($model, &$map) {
        $map['fromusername'] = array('neq', '');
        $map['msgtype'] = array(array('eq', 'text'), array('eq', 'image'), 'or');
        $map['receivedtime'] = array('exp', 'is not null');
    }

    /**
     * 数据列表过滤
     * @param type $data
     */
    protected function _index_data_filter(&$data) {
        foreach ($data as &$vo) {
            $vo['receivedtime'] = to_date($vo['receivedtime']);
            $vo['user'] = M('WechatMember')->where(array('openid' => $vo['fromusername']))->find();
            switch ($vo['msgtype']) {
                case 'image':
                    $vo['content'] = show_img_link($vo['PicUrl']);
                    break;
                case 'event':
                    switch ($vo['event']) {
                        case 'LOCATION':
                            $url = "http://apis.map.qq.com/ws/staticmap/v2?key=ZBHBZ-CHQ2G-RDXQF-I5TUX-SAK53-A5BZT&size=500x400&center={$vo['latitude']},{$vo['longitude']}&zoom=12&markers={$vo['latitude']},{$vo['longitude']}&name=map.jpg";
                            $vo['msgtype'] = '自动推送位置';
                            $vo['content'] = show_img_link($url);
                            break;
                        case 'subscribe':
                            $vo['msgtype'] = '加入关注';
                            $vo['content'] = '主动加入关注';
                            break;
                        case 'unsubscribe':
                            $vo['msgtype'] = '取消关注';
                            $vo['content'] = '主动取消关注';
                            break;
                        case 'CLICK':
                            $vo['msgtype'] = '菜单关键字';
                            $vo['content'] = M('WechatMenu')->where(array('id' => intval(str_ltrim($vo['eventKey'], 'mid'))))->getField('name');
                            break;
                        case 'VIEW':
                            $vo['msgtype'] = '菜单链接内容';
                            $vo['content'] = $vo['eventKey'];
                            break;
                        case 'scancode_waitmsg':
                        case 'scancode_push':
                        case 'pic_sysphoto':
                        case 'pic_photo_or_album':
                        case 'pic_weixin':
                        case 'location_select':
                            $vo['msgtype'] = $this->msgType[$vo['event']];
                            $vo['content'] = M('WechatMenu')->where(array('id' => intval(str_ltrim($vo['eventKey'], 'mid'))))->getField('name');
                            break;
                    }
                    break;
                case 'location':
                    $url = "http://apis.map.qq.com/ws/staticmap/v2?key=ZBHBZ-CHQ2G-RDXQF-I5TUX-SAK53-A5BZT&size=500x400&center={$vo['location_x']},{$vo['location_y']}&zoom=12&markers={$vo['location_x']},{$vo['location_y']}&name=map.jpg";
                    $vo['content'] = show_img_link($url);
            }
            if (isset($this->msgType[$vo['msgtype']])) {
                $vo['msgtype'] = $this->msgType[$vo['msgtype']];
            }
        }
    }

    protected function _edit_filter($model, $data) {
        set_select_menu('Wechat/Msg/index');
        C('HANDLER_IMG_ERROR', false);
        if (IS_POST) {
            $wechat = $this->getInstanceWechat();
            $model = M("WechatMsg");
            $map = array();
            $map['fromusername'] = $this->wechat_config['openid'];
            $map['tousername'] = I('post.ToUser');
            $map['receivedtime'] = time();
            $map['msgtype'] = 'text';
            $map['event'] = 'text';
            $map['content'] = I('post.reply');
            $result = $this->_save($map, $model);
            if ($result) {
                $parame = array(
                    "touser"  => "{$map['tousername']}",
                    "msgtype" => "text",
                    "text"    => array(
                        "content" => "{$map['content']}"
                    )
                );
                $wechat->sendCustomMessage($parame);
                $this->success("客服回复成功...");
            } else {
                $this->error("客服回复失败...");
            }
        }
    }

    protected function _form_filter() {
        $map = array();
        $map['id'] = I('get.id', 0, 'intval');
        $wechatMsg = M("WechatMsg")->where($map)->find();
        $member = M('WechatMember')->where(array('openid' => $wechatMsg['fromusername']))->find();
        $msgs = M('WechatMember')
                ->alias('mb')
                ->field('mb.nickname name,mb.headimgurl url,mb.openid,ms.*')
                ->join("left join __WECHAT_MSG__ ms on (mb.openid=ms.fromusername or mb.openid=ms.tousername)")
                ->where("(ms.fromusername='{$member['openid']}' or ms.tousername='{$member['openid']}') and ((ms.event!='subscribe' and ms.event!='unsubscribe' and ms.event!='SCAN') or ms.event is null)")
                ->order('ms.receivedtime ASC')
                ->select();
        $result = array();
        $i = 0;
        foreach ($msgs as $val) {
            $res = $this->_showSwitch($val);
            if ($val['openid'] == $val['fromusername']) {
                $result[$i]["name"] = $val['name'];
                $result[$i]["url"] = $val['url'];
                $result[$i]["content"] = $res['content'];
                $result[$i]["user"] = $member;
            } else {
                $result[$i]["name"] = '系统回复';
                $result[$i]["url"] = $val['img'];
                $result[$i]["content"] = $val['content'];
            }
            $result[$i]["receivedtime"] = $val['receivedtime'];
            $result[$i]["openid"] = $val['openid'];
            $i++;
        }
        $this->list = $result;
        $this->user = $member;
    }

    protected function _showSwitch($vo) {
        switch ($vo['msgtype']) {
            case 'image':
                $vo['content'] = show_img_link($vo['PicUrl']);
                break;
            case 'text':
                $vo['msgtype'] = '文本消息';
                $vo['content'] = $vo['content'];
                break;
            case 'news':
                $vo['msgtype'] = '图文消息';
                $vo['content'] = $vo['content'];
                break;
            case 'event':
                switch ($vo['event']) {
                    case 'LOCATION':
                        $url = "http://apis.map.qq.com/ws/staticmap/v2?key=ZBHBZ-CHQ2G-RDXQF-I5TUX-SAK53-A5BZT&size=500x400&center={$vo['latitude']},{$vo['longitude']}&zoom=12&markers={$vo['latitude']},{$vo['longitude']}&name=map.jpg";
                        $vo['msgtype'] = '自动推送位置';
                        $vo['content'] = show_img_link($url);
                        break;
                    case 'subscribe':
                        return FALSE;
                    case 'unsubscribe':
                        return FALSE;
                    case 'CLICK':
                        $vo['msgtype'] = '菜单关键字';
                        $vo['content'] = M('WechatMenu')->where(array('id' => intval(str_ltrim($vo['eventKey'], 'mid'))))->getField('name');
                        break;
                    case 'VIEW':
                        $vo['msgtype'] = '菜单链接内容';
                        $vo['content'] = $vo['eventKey'];
                        break;
                    case 'scancode_waitmsg':
                    case 'scancode_push':
                    case 'pic_sysphoto':
                    case 'pic_photo_or_album':
                    case 'pic_weixin':
                    case 'location_select':
                        $vo['msgtype'] = $this->msgType[$vo['event']];
                        $vo['content'] = M('WechatMenu')->where(array('id' => intval(str_ltrim($vo['eventKey'], 'mid'))))->getField('name');
                        break;
                }
                break;
            case 'location':
                $url = "http://apis.map.qq.com/ws/staticmap/v2?key=ZBHBZ-CHQ2G-RDXQF-I5TUX-SAK53-A5BZT&size=500x400&center={$vo['Location_X']},{$vo['Location_Y']}&zoom=12&markers={$vo['Location_X']},{$vo['Location_Y']}&name=map.jpg";
                $vo['content'] = show_img_link($url);
        }
        return $vo;
    }

}
