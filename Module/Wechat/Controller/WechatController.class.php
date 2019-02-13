<?php

namespace Wechat\Controller;

use Admin\Controller\AdminController;
use Library\Util\Api\Wechat;
use Library\Util\Api\WechatPay;

/**
 * 公众号基础模块
 */
class WechatController extends AdminController {

    /**
     * 设置分类名称
     * @var type 
     */
    public $gtitle = '公众号管理';

    /**
     * 微信配置信息
     * @var type 
     */
    protected $wechat_config = null;

    /**
     * 微信接口SDK
     * @var type 
     */
    protected $wechat = null;

    /**
     * 微信支付SDK
     * @var type 
     */
    protected $wechatPay = null;

    /**
     * 设置并应用微信配置数据
     * 
     * @return boolean
     */
    public function applyWechatConfig($reload = false) {
        $this->wechat_config = session('wechat_config');
        if (empty($this->wechat_config) || $reload) {
            $map = array();
            $map['id'] = '1';
            $map['status'] = '2';
            $this->wechat_config = M('WechatConfig')->where($map)->find();
            if ($this->wechat_config) {
                session('wechat_config', $this->wechat_config);
                return true;
            }
            session('wechat_config', null);
        }
        return false;
    }

    /**
     * 获取微信SDK操作对象
     * @return Wechat
     */
    protected function getInstanceWechat() {
        if (is_null($this->wechat)) {
            is_null($this->wechat_config) && $this->applyWechatConfig();
            $this->wechat = new Wechat($this->wechat_config);
        }
        return $this->wechat;
    }

    /**
     * 获取微信支付SDK操作对象
     * @return WechatPay
     */
    protected function getInstanceWechatPay() {
        if (is_null($this->wechatPay)) {
            is_null($this->wechat_config) && $this->applyWechatConfig();
            $this->wechatPay = new WechatPay($this->wechat_config);
        }
        return $this->wechatPay;
    }

    /**
     * 微信关键字操作
     * @param \Think\Model $model
     * @param array $data
     * @param string $type
     * @return type
     */
    protected function _writeKeys($model, $data, $type = 'image') {
        $map = array();
        $map['table'] = $model->getTableName();
        $map['table_field'] = $model->getPk();
        $map['table_field_value'] = $data[$model->getPk()];
        $map['type'] = $type;
        foreach ($data as &$vo) {
            $vo = strip_tags($vo);
        }
        $_data['keys'] = $data['keys'];
        $_data['title'] = $data['title'];
        $content = strip_tags($data['content']);
        $_data['content'] = mb_strlen($content) > 100 ? (mb_substr($content, 0, 100) . '...') : $content;
        empty($data['link']) or $_data['link'] = to_domain($data['link']);
        empty($data['url']) or $_data['url'] = to_domain($data['url']);
        $_data['create_by'] = get_user_id();
        //如果没有设置关键字，则实行删除记录操作
        if (empty($_data['keys'])) {
            return M('WechatKeys')->where($map)->delete();
        } else {
            return $this->_save(array_merge($map, $_data), D('WechatKeys'), $map);
        }
    }

}
