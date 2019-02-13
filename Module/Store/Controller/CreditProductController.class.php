<?php

namespace Store\Controller;

/**
 * 产品管理控制器
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 14:01:45
 */
class CreditProductController extends StoreController {

    /**
     * 设置模块名称
     * @var type 
     */
    public $ptitle = '积分商品管理';

    /**
     * 设定可访问的操作
     * @var type 
     */
    public $access = array(
        'index'     => '产品列表',
        'add'       => '添加产品',
        'edit'      => '编辑产品',
        'del'       => '删除产品',
        'recommend' => '推荐产品',
        'delmore'   => '删除更多',
        'resume'    => '启用产品',
        'forbid'    => '禁用产品',
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
    protected $_bind_model = 'StoreCreditProduct';

    /**
     * 产品分类编辑前置方法
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/04 14:03:58
     */
    protected function _form_filter(&$model, &$data) {
        $extend_id = array_unique(I("post.extend_id"));
        $_POST['extend_cat'] = implode(',', $extend_id);
        if (IS_POST) {
            $store = I('post.params', array()); //产品详细参数
            if (!empty($store)) {
                $_POST['store_nums'] = 0;     //库存
                $_POST['sell_credit'] = 0.00;     //兑换积分
                foreach ($store as $key => &$vo) {
                    if (strpos($key, '_split_store') !== false) {
                        $_POST['store_nums']+=$vo;
                    }
                    if (strpos($key, '_split_credit') !== false) {
                        if ($_POST['sell_credit'] == 0.00) {//得出第一个规格的价格
                            $_POST['sell_credit'] = $vo;
                        }
                    }
                }
            }
            $_POST['params'] = json_encode($store);
            $_POST['model_params'] = json_encode(I('post.model_params', array()));
        } else {
            if (I('get.action', 0) === 'getModelJSON') {
                $where = array();
                $where['id'] = I('get.model_id', 0);
                echo M('StoreCreditProductModel')->where($where)->getField('params');
                die();
            } else {
                /* 分类数据 */
                $map = array();
                $map['status'] = 2;
                $map['id'] = array('neq', I('get.id', 0, 'intval'));
//                $map['api_id'] = $this->api['id'];
                //产品分类树
                $catList = M('StoreCreditProductCat')->where($map)->select();
                array_unshift($catList, array('id' => 0, 'pid' => 'productCats', 'name' => '产品分类'));
                $catList = get_select_tree($catList);
                $this->assign('catList', $catList);

                //扩展分类
                $extend_cat = M("StoreCreditProduct")->where(array("id" => I('get.id')))->getField("extend_cat");
                if (!empty($extend_cat)) {
                    $extendCat = explode(',', $extend_cat);
                    $this->assign('extendCat', $extendCat);
                }

                /* 模型数据 */
//                $where = array();
//                $where['api_id'] = $this->api['id'];
                $modelList = M('StoreCreditProductModel')->select();
                array_unshift($modelList, array('id' => 0, 'name' => '请选择规格'));
                $this->modelList = $modelList;
            }
        }
    }

    /**
     * 产品导入前置方法
     */
    public function _before_leadTo() {
        //列出产品分类树
        $catList = M('StoreCreditProductCat')->where($map)->select();
        array_unshift($catList, array('id' => 0, 'pid' => -1, 'name' => '产品分类'));
        $catList = get_select_tree($catList);
        $this->assign('catList', $catList);
    }

    public function leadTo() {
        if (IS_POST) {
            //获取导入的Excel数据
            $filename = UPLOAD_PATH . 'tmp/' . date('YmdHis') . rand(100, 999) . '.tmp';
            file_put_contents($filename, file_get_contents(to_domain(I("post.myfile"))));
            $data = read_excel($filename, 2);
            //p($data);
            $cat_id = I("post.cat_id");
            //将数据与表字段一一对应
            $excelData = array();
            foreach ($data as $key => $value) {
                $excelData[$key] = array(
                    'cat_id'        => $cat_id,
                    'name'          => $value['0'],
                    'product_sn'    => $value['1'],
                    'logo'          => $value['2'],
                    'img'           => $value['3'],
                    'store_nums'    => $value['4'],
                    'sell_credit'   => $value['5'],
                    'market_credit' => $value['6'],
                    'description'   => $value['7'],
                    'property'      => $value['8'],
                    'notice'        => $value['9']
                );
            }
            $result = $this->_saveAll($excelData);
            if ($result['status'] === 1) {
                $this->success('数据导入成功，刷新数据中...');
            } else {
                $this->error('数据导入失败，请稍后再试！');
            }
        } else {
            $this->display();
        }
    }

    /**
     * 产品推荐方法
     * 
     * @author tanglinjun
     * @date 2014-12-10
     */
    public function recommend() {
        $id = intval(I("post.id"));
        $recommend = I("post.is_recommend", 0, 'intval');
        if (!$id)
            $this->error("数据错误");
        if ($recommend === 0) {
            $result = M("StoreCreditProduct")->where(array('id' => $id))->setField('is_recommend', 1);
            $result ? $this->success('数据状态操作成功！') : $this->error('数据状态操作失败！');
        } elseif ($recommend === 1) {
            $result = M("StoreCreditProduct")->where(array('id' => $id))->setField('is_recommend', 0);
            $result ? $this->success('数据状态操作成功！') : $this->error('数据状态操作失败！');
        }
    }

    public function delmore() {
        $ids = I('post.ids', array());
        $result = M('StoreCreditProduct')->where(array('id' => array('in', $ids)))->delete();
        $result ? $this->success('数据状态操作成功！') : $this->error($result . M()->_sql());
    }

}
