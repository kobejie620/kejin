<?php

namespace Wechat\Controller;

/**
 * 微信菜单控制器
 * @author Anyon <zoujingli@qq.com>
 * @date 2014/08/21 09:45
 */
class MenuController extends WechatController {

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'WechatMenu';

    /**
     * 模块标题
     * @var type 
     */
    public $ptitle = '微信菜单管理';

    /**
     * 定义可访问的方法名
     * @var type 
     */
    public $access = array(
        'index'  => '菜单列表',
        'add'    => '添加菜单',
        'edit'   => '编辑菜单',
        'del'    => '删除菜单',
        'sync'   => '推送菜单',
        'cancel' => '取消菜单',
        'resume' => '启用菜单',
        'forbid' => '禁用菜单',
    );

    /**
     * 设置可用于菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '菜单列表',
    );

    /**
     * 关闭分布
     * @var type 
     */
    protected $_page_on = false;

    /**
     * 数据过滤
     * @param type $list
     */
    protected function _index_data_filter(&$list) {
        $list = get_select_tree($list);
        $_info = array(
            'null'               => '',
            'text'               => '回复：文本消息',
            'keys'               => '回复：关键字',
            'link'               => '显示：链接地址',
            'scancode_waitmsg'   => '功能：扫码带提示',
            'scancode_push'      => '功能：扫码推事件',
            'pic_sysphoto'       => '功能：系统拍照发图',
            'pic_photo_or_album' => '功能：拍照或者相册发图',
            'pic_weixin'         => '功能：微信相册发图',
            'location_select'    => '功能：发送位置',
        );
        foreach ($list as &$vo) {
            $vo['lower'] = get_array_tree_lower_ids($list, $vo['id']);
            $vo['title'] = $vo['spl'] . $vo['name'];
            switch ($vo['type']) {
                case 'null':
                    $vo['info'] = '';
                    break;
                case 'text':
                case 'keys':
                case 'link':
                    $vo['info'] = "<font color='#989898'>{$_info[$vo['type']]}</font>";
                    break;
                case 'scan':
                case 'pic':
                case 'location':
                    $vo['info'] = "<font color='#989898'>{$_info[$vo[$vo['type']]]}</font>";
                    break;
            }
        }
    }

    /**
     * 推送菜单数据到微信
     */
    public function sync() {
        $map = array();
        $map['status'] = 2;
        $list = M($this->_bind_model)->where($map)->order('sort asc,id desc')->select();
        $data = $this->_bind_menu($list);
        $menu = array('button' => $data);
        $wehcat = $this->getInstanceWechat();
        if ($wehcat->createMenu($menu)) {
            $this->success('微信菜单推送成功，请重新关注微信以便快速效！');
        } else {
            $this->error('微信菜单推送失败，请稍候再试，' . $wehcat->errMsg);
        }
    }

    /**
     * 处理菜单数据
     * 
     * @param type $list
     * @param type $pid
     * @return type
     */
    protected function _bind_menu($list, $pid = 0) {
        $data = array();
        foreach ($list as $vo) {
            if (intval($vo['pid']) !== intval($pid)) {
                continue;
            }
            switch ($vo['type']) {
                case 'text':
                case 'keys':
                    $data[] = array(
                        'type' => 'click',
                        'name' => $vo['name'],
                        'key'  => 'mid' . $vo['id']
                    );
                    break;
                /* 菜单链接 */
                case 'link':
                    if (stripos($vo['url'], 'http') !== 0) {
                        $vo['url'] = U($vo['url']);
                    }
                    $data[] = array(
                        'type' => 'view',
                        'name' => $vo['name'],
                        'url'  => to_domain($vo['url']),
                    );
                    break;

                /* 菜单扫码 */
                case 'scan':
                /* 菜单发图 */
                case 'pic':
                /* 菜单发位置 */
                case 'location':
                    $data[] = array(
                        'type' => $vo[$vo['type']],
                        'name' => $vo['name'],
                        'key'  => 'mid' . $vo['id'],
                    );
                    break;
                /* 一级菜单 */
                case 'null':
                    $data[] = array(
                        'name'       => $vo['name'],
                        'sub_button' => $this->_bind_menu($list, $vo['id'])
                    );
                    break;
            }
        }
        /*
         * 去除无效的数据
         */
        foreach ($data as $key => &$vo) {
            if (isset($vo['sub_button']) && (empty($vo['sub_button']) || count($vo['sub_button']) > 5)) {
                unset($data[$key]);
            }
        }
        return array_values($data);
    }

    /**
     * 取消微信菜单
     */
    public function cancel() {
        if ($this->getInstanceWechat()->deleteMenu()) {
            $this->success('取消菜单成功，<br/>请重新关注微信以便快速效！');
        } else {
            $this->error('菜单取消菜单，请稍候再试！');
        }
    }

}
