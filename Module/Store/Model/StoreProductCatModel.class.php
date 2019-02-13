<?php

namespace Store\Model;

/**
 * 产品分类模型
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 10:24:05
 */
class StoreProductCatModel extends StoreModel {

    /**
     * 获取产品分类数据
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:04:57
     * @return array
     */
    public function getCatList($map = array(), $type = 'list') {
//        $map['api_id'] = $this->getApiId();
        $index = md5(md5($map) . md5($type));
        static $_data = array();
        if (isset($_data[$index])) {
            return $_data[$index];
        }
        $list = $this->where($map)->select();
        switch ($type) {
            case 'list':
                return $_data[$index] = $list;
            case 'select';
                return $_data[$index] = get_select_tree($list);
        }
    }

}
