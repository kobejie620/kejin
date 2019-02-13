<?php

namespace Wechat\Controller;

/**
 * 会员管理控制器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/23 15:54:15
 */
class MemberController extends WechatController {

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'WechatMember';

    /**
     * 设置网页标题
     * @var type 
     */
    public $ptitle = '会员管理';

    /**
     * 设置模块可访问列表
     * @var type 
     */
    public $access = array(
        'index' => '会员列表',
        'edit'  => '编辑会员',
        'sync'  => '获取会员',
    );

    /**
     * 指定可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '会员列表',
    );

    /**
     * 首页前置方法
     * 
     */
    public function _before_index() {
        //获取分组列表
        $map = array();
        $this->assign("groups", M("WechatMemberGroup")->where($map)->select());
    }

    /**
     * 模型关联操作
     * @param \Think\Model $model
     */
    protected function _index_list_filter(&$model, &$map) {
        $map['m.status'] = '2'; 
        $model->field('m.*,g.name "g.name",g.count "g.count",mr.name as myname,mr.sid , mr.phone')
                ->alias('m')
                ->join('left join __WECHAT_MEMBER_GROUP__ g on m.group_id=g.id')
                ->join('left join __MEMBER__ mr on m.openid = mr.openid');
    }

    /**
     * 会员列表数据处理
     * @param type $data
     */
    protected function _index_data_filter(&$data) {
        foreach ($data as &$row) {
            $row['myaddress'] = $row['country'] . $row['province'] . $row['city'];
            $row['store_name'] = M('Stores')->field('name')->where(array('id' => $row['sid']))->find();
            $row['store_name'] = $row['store_name']['name'];
        }
    }

    /**
     * 同步观注用户列表
     */
    public function sync($next_openid = '') {
        /** step 1. 获取远程会员分组 */
        /** step 2. 获取远程会员信息 */
        if (empty($next_openid)) {
            $this->_syncGroup();
            M($this->_bind_model)->setField('status', '1');
        }
        val('_sync_member_num', 0);
        while ($next_openid !== false) {
            $next_openid = $this->_doingOpenids($next_openid);
        }
        $this->progress('会员信息获取完成。', 1);
    }

    /**
     * 获取会员分组
     */
    protected function _syncGroup() {
        $model = D('WechatMemberGroup');
        $groups = $this->getInstanceWechat()->getGroup();
        $this->progress("正在获取远程分组信息，请稍后...");
        $model->startTrans();
        $result = array('status' => 1);
        $model->where('1=1')->delete();
        foreach ($groups['groups'] as $value) {
            $value['status'] = 2;
            $value['create_by'] = get_user_id();
            $map = array();
            $map['id'] = $value['id'];
            $result = $this->_save($value, $model, $map);
            if (!$result['status']) {
                $model->rollback();
                $this->progress("获取远程会员分组数据失败，请稍后再试", 0);
            }
        }
        $model->commit();
        $this->progress("获取远程分组信息获取完成...");
    }

    /**
     * 处理Openid数据
     * 
     * @param type $next_openid
     * @return boolean
     */
    protected function _doingOpenids($next_openid = '') {
        $result = $this->getInstanceWechat()->getUserList($next_openid);
        $total = $result['total'];
        $this->progress("正在获取会员信息(0/{$total})，请稍后...");
        if ($result !== false) {
            $next_openid = $result['next_openid'];
            $openids = $result['data']['openid'];
            foreach ($openids as $openid) {
                $result = $this->_saveUserInfo($openid);
                $sync_number = val('_sync_member_num') + 1;
                val('_sync_member_num', $sync_number);
                $this->progress("正在获取会员信息({$sync_number}/{$total})，请稍后...");
            }
            return empty($next_openid) ? false : $next_openid;
        } else {
            return false;
        }
    }

    /**
     * 更新用户的详细信息
     * @param type $openid
     * @return type
     */
    protected function _saveUserInfo($openid) {
        $where = array();
        $where['openid'] = $openid;
        $user = array_merge($where, (array) $this->wechat->getUserInfo($where['openid']));
        $user['group_id'] = $this->wechat->getUserGroup($openid);
        if ($user['group_id'] === false) {
            $this->progress('获取用户分组信息失败,' . $this->wechat->errMsg, 0);
        }
        $_sex = array(1 => '男', 2 => '女');
        $user['sex'] = isset($_sex[$user['sex']]) ? $_sex[$user['sex']] : '保密';
        $user['update_date'] = get_now_date();
        $user['create_date'] = to_date($user['subscribe_time']);
        $user['status'] = 2;
        return $this->_save($user, D($this->_bind_model), $where);
    }

}
