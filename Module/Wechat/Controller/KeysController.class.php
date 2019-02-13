<?php

namespace Wechat\Controller;

/**
 * 接口关键字管理操作器
 * @author Anyon <zoujingli@qq.com>
 * @date 2014/09/21 09:46
 */
class KeysController extends WechatController {

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'WechatKeys';

    /**
     * 定义模块标题
     * @var type 
     */
    public $ptitle = '微信关键字管理';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index'     => '关键字列表',
        'createQrc' => '生成二维码',
        'del'       => '删除关键字',
    );

    /**
     * 定义可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '关键字列表',
    );

    /**
     * 创建二维码
     */
    public function createQrc() {
        $keys = I('get.keys', '', 'trim');
        if (!empty($keys)) {
            $map = array();
            $map['keys'] = $keys;
            $map['qrc'] = array(array('eq', ''), array('exp', 'is null'), 'or');
            if (M('WechatKeys')->where($map)->count() < 1) {
                return $this->success('关键字二维码已经存在');
            }
            $ticket_data = $this->getInstanceWechat()->getQRCode($keys, 2);
            if ($ticket_data === false) {
                $this->error('生成关键字二维码失败，' . $this->getInstanceWechat()->errMsg);
            }
            $url = $this->getInstanceWechat()->getQRUrl($ticket_data['ticket']);
            if ($url === false) {
                $this->error('生成关键字二维码失败，' . $this->getInstanceWechat()->errMsg);
            }
            $data = array();
            $data['keys'] = $keys;
            $data['qrc'] = $url;
            $data['create_by'] = get_user_id();
            $this->_save($data, M('WechatKeys'), array('keys' => $keys));
            $this->success('关键字二维码生成成功');
        }
        $this->error('生成关键字二维码失败，关键字为空');
    }

}
