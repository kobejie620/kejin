<?php

namespace Wechat\Controller;

class ConfigController extends WechatController {

    /**
     * 模块标题
     * @var type 
     */
    public $ptitle = '公众号配置';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index' => '配置信息',
        'edit'  => '更改配置',
    );

    /**
     * 指定可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '配置信息'
    );

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'WechatConfig';

    /**
     * 首页列表前置方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:52:23
     */
    public function index() {
        $this->applyWechatConfig(true);
        $this->assign('vo', $this->wechat_config);
        $this->assign('ptitle', $this->ptitle);
        $this->display();
    }

    /**
     * 微信配置编辑入口
     */
    public function edit() {
        $action = I('post.action');
        if (in_array($action, array('_autobind', '_saveinfo'))) {
            $result = $this->_save($_POST, D($this->_bind_model));
            if ($result['status']) {
                $this->applyWechatConfig(true);
                $this->$action(1, $result);
                die();
            }
        }
        $this->error('操作失败，请刷新页面再试！');
    }

    /**
     * 执行绑定微信公众号
     * 
     * @param type $id
     */
    private function _autobind($id) {
        $this->_autoBindWechat($id);
    }

    /**
     * 保存更新微信配置信息
     * 
     * @param type $id
     */
    private function _saveinfo($id, $result) {
        $result['url'] = get_referer_url(U('Wechat/Config/index'));
        $this->ajaxReturn($result);
    }

    /**
     * 自动绑定微信公众号
     * 
     * @param type $id API数据ID
     */
    protected function _autoBindWechat($id) {

        empty($id) && $this->error('操作参数错误，请返回重试！');

        $map = array();
        $map['status'] = 2;
        $map['id'] = $id;
        $config = M($this->_bind_model)->where($map)->find();

        /* 必需参数检测 */
        if (empty($config) || empty($config['username']) || empty($config['password'])) {
            $this->error('此接口配置信息不可用，不能进行平台数据同步！');
        }

        /* 生成新的参数并保存 */
        if (empty($config['token']) || empty($config['encodingaeskey'])) {
            if (empty($config['encodingaeskey'])) {
                $config['encodingaeskey'] = substr(uniqid() . uniqid() . uniqid() . uniqid(), 0, 43);
            }
            empty($config['token']) && $config['token'] = 'token';
            $this->_save($config, D($this->_bind_model));
        }

        /* 计算接口的回调地址 */
        $config['redirectUri'] = to_domain(U('/api'));

        /* 构建对接服务 */
        $apiService = '\\Library\\Util\\Api\BindWechat';

        $api = new $apiService($config);

        /* 设置配置接口 */
        if (true !== $api->autoBind()) {
            $this->error('设置接口配置失败，' . $api->getError());
        }
        /* 获取信息并更新记录 */
        $new_config = array_merge($config, $api->getApiInfo());
        $result = $this->_save($new_config, D($this->_bind_model), array('id' => $id));
        if ($result['status']) {
            $url = get_referer_url(U('Wechat/Config/index'));
            $this->success('平台数据获取成功！', get_referer_url($url));
        } else {
            $this->error('平台数据保存失败，请稍候再试！');
        }
    }

}
