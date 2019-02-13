<?php

namespace Store\Controller;

/**
 * 产品管理控制器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 14:01:45
 */
class ProductController extends StoreController {

    /**
     * 设置模块名称
     * @var type 
     */
    public $ptitle = '产品管理';

    /**
     * 设定可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '产品列表',
        'add' => '添加产品',
        'edit' => '编辑产品',
        'del' => '删除产品',
        'recommend' => '推荐产品',
        'resume' => '启用产品',
        'forbid' => '禁用产品',
    );

    /**
     * 指定可设置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '产品列表'
    );

    /**
     * 绑定操作模型
     * @var type 
     */
    protected $_bind_model = 'StoreProduct';

    /**
     * 产品分类编辑前置方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:03:58
     */
    protected function _form_filter(&$model, &$data) {
        $extend_id = array_unique(I("post.extend_id"));
        $data['extend_cat'] = implode(',', $extend_id);
        if (IS_POST) {
            $store = I('post.params', array()); //产品详细参数
            if (!empty($store)) {
                $data['store_nums'] = 0;     //库存
                $data['sell_price'] = 0.00;     //折扣价
                $data['original_price'] = 0.00 ;   //原价
                foreach ($store as $key => &$vo) {
                    if (strpos($key, '_split_store') !== false) {
                        $data['store_nums']+=$vo;
                    }
                    if (strpos($key, '_split_price') !== false) {
                        if ($data['sell_price'] == 0.00) {//得出第一个规格的折扣价
                            $data['sell_price'] = $vo;
                        }
                    }
                    if (strpos($key,'_split_original') !== false) {
                        if ($data['original_price'] == 0.00) {   //得出第一个规格的原价
                            $data['original_price'] = $vo;
                        }
                    }
                }
                
            }
            $data['params'] = json_encode($store);
            $data['model_params'] = json_encode(I('post.model_params', array()));
        } else {
            if (I('get.action', 0) === 'getModelJSON') {
                $where = array();
                $where['id'] = I('get.model_id', 0);
                $where['status'] = '2';
                echo M('StoreProductModel')->where($where)->getField('params');
                die();
            } else {
                /* 分类数据 */
                $map = array();
                $map['status'] = '2';
                $map['id'] = array('neq', I('get.id', 0, 'intval'));
                //产品分类树
                $catList = M('StoreProductCat')->where($map)->select();
                array_unshift($catList, array('id' => 0, 'pid' => 'productCats', 'name' => '产品分类'));
                $catList = get_select_tree($catList);
                $this->assign('catList', $catList);

                //扩展分类
                $extend_cat = M("StoreProduct")->where(array("id" => I('get.id')))->getField("extend_cat");
                if (!empty($extend_cat)) {
                    $extendCat = explode(',', $extend_cat);
                    $this->assign('extendCat', $extendCat);
                }

                /* 模型数据 */
                $modelList = M('StoreProductModel')->where(array('status' => '2'))->select();
                array_unshift($modelList, array('id' => 0, 'name' => '请选择规格'));
                $this->modelList = $modelList;
            }
        }
    }

    /**
     * 产品推荐方法
     * @author tanglinjun
     * @date 2014-12-10
     */
    public function recommend() {
        $id = intval(I("post.id"));
        $recommend = I("post.is_recommend", 0, 'intval');
        if (!$id)
            $this->error("数据错误");
        if ($recommend === 0) {
            $result = M("StoreProduct")->where(array('id' => $id))->setField('is_recommend', 1);
            $result ? $this->success('数据状态操作成功！') : $this->error('数据状态操作失败！');
        } elseif ($recommend === 1) {
            $result = M("StoreProduct")->where(array('id' => $id))->setField('is_recommend', 0);
            $result ? $this->success('数据状态操作成功！') : $this->error('数据状态操作失败！');
        }
    }

    protected function _form_success($model, $data) {
        if (!empty($data['id'])) {
            $map = array();
            $map['cid'] = $data['id'];
            M('StoreOrderCar')->where($map)->setField('status', '3');
        }
    }

    protected function _add_filter($model,$vo){
        if(IS_POST){
            $result = M("StoreProduct")->where(array('code' =>I('post.code')))->find();
            if($result){
                $this->error('已经存在这个产品编号了，请注意辨别不同的产品编号  '); 
            }
        }
    }

}
