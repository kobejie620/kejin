<?php

namespace Wap\Widget;

use Think\Controller;

/**
 * 系统菜单挂件
 * 
 * @author zoujingli <zoujingli@qq.com>
 */
class PageWidget extends Controller {

    protected $modules = null;

    /**
     * 显示挂件数据
     * @param type $modules
     * @return type
     */
    public function show($modules) {
        if (empty($modules)) {
            return null;
        }
        $list = $this->_getData($modules);
        foreach ($list as &$vo) {
            $vo['content'] = json_decode($vo['content'], true);
            $this->assign('_data_', $vo);
            $this->display(T('Wap@Page/' . $vo['type']));
        }
    }

    /**
     * 获取对应挂件的数据
     * @param type $ids
     * @return type
     */
    protected function _getData($ids) {
        $map = array();
        $map['status'] = 2;
        $map['id'] = array('in', $ids);
        return $this->modules = M('SitePageModule')->where($map)->select();
    }

}
