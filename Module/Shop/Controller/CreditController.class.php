<?php

namespace Shop\Controller;

/**
 * 积分商城
 */
class CreditController extends ShopController {

    /**
     * 绑定模型
     * @var type 
     */
    protected $_bind_model = "StoreCreditProduct";

    /**
     * 绑定默认列表条件
     * @var type 
     */
    protected $_bind_map = array('status' => 2);

    /**
     * 数据过滤处理
     * @param type $data
     * @param type $model
     */
    protected function _index_data_filter(&$data, &$model) {
        //获取商城信息
        $info = M("Store")->where(array("id" => 1))->find();
        $info['link'] = explode('|', "{$info['link']}");
        if (!empty($info['url'])) {
            redirect($info['url']);
        }
        $this->assign('ptitle', $info['name']);
        $this->store = $info;
    }

    /**
     * 处理产品详情页数据
     * @param type $model
     * @param type $vo
     */
    protected function _form_filter(&$model, &$vo) {
        //产品详细图片
        $vo['img'] = explode('|', "{$vo['img']}");
        //产品模型参数解析
        $productModel = D('Store/StoreProductModel');
        $vo['model_params'] = $productModel->parseParams($vo['model_params']);
    }

    /**
     * 订单准备页面，选地址，选快递...
     * @author Zoe
     * @date 2014-12-11
     */
    public function check() {
        //直接兑换
        $info['param'] = I("get.param");
        $info['credit'] = I("get.credit");
        $info['num'] = I("get.num");
        $info['cid'] = I("get.cid");
        $info['id'] = 0;
        $list[] = $info;

        //如果没有get传过来的参数，则获取默认收获地址
        $addressid = I("get.addressid");
        $map = array();
        $map['openid'] = $this->openid;
        if (empty($addressid)) {
            $map['is_default'] = 2; //默认地址
        } else {
            $map['id'] = $addressid; //选中地址
        }
        $address = M("MemberAddress")->where($map)->find();
        $total_credit = $this->_getTotal($list);
        //将产品信息转为JSON传到前台
        $data = json_encode($list);

        //获取用户当前积分值
        $this->integral = M("Member")->where(array("openid" => $this->openid))->getField("integral");

        $free_express = 0;
        $times = array();
        foreach ($list as $val) {
            //获取商品信息
            $product = M("StoreCreditProduct")->where(array('id' => $val['cid']))->find();
            $arr = explode('_split_', $val['param']);
            $product["all_params"][$arr[0]] = $arr[1];
            $product['num'] = $val['num'];
            $product['credit'] = $val['credit'];
            $products[] = $product;

            //检测库存是否充足  add by tanglinjun 2015-01-20
            $store = get_product_store($val['cid'], $val['param']);
            if (empty($store)) {
                $store = M("StoreCreditProduct")->where(array('id' => $val['cid']))->getField("store_nums");
            }
            $surplus = $store - $val['num'];
            if ($surplus < 0) {
                R('Wap/Error/show', array('库存不足', '请浏览其他产品！'));
            }

            //检查产品购买次数是否符合要求 add by tanglinjun 2015-01-27
            $productInfo = M("StoreCreditProduct")->field("times_limit,free_express")->where(array("id" => $val['cid']))->find();
            if ($productInfo['times_limit'] == '0') {
                R('Wap/Error/show', array('本产品暂时不能兑换', '请浏览其他产品！'));
            } elseif ($productInfo['times_limit'] > 0) {
                //记录商品次数
                $times[$val['cid']] += $val['num'];
            }

            //处理邮费策略的问题 add by tanglinjun 2015-01-28
            //在多产品订单中，有一个产品免邮，那么都免邮。
            if ($productInfo['free_express'] == '1') {
                $free_express = 1;
            }
        }

        //输出产品列表
        $this->assign("product", $products);

        //获取快递信息
        $map = array();
        $map['status'] = 2;
        $deliverys = M("StoreDelivery")->where($map)->select();
        $this->assign('deliverys', $deliverys);

        $this->assign('total_credit', $total_credit);
        $this->assign("data", $data);
        $this->assign("address", $address);
        $this->assign("free_express", $free_express);
        $this->assign("ptitle", "提交订单");
        $this->display();
    }

    /**
     * 计算数据
     * @param type $data
     * @return type
     */
    private function _getTotal(&$data) {
        $total_credit = 0;
        foreach ($data as &$row) {
            $total_credit += $row['num'] * $row['credit'];
            //$row['product'] = M('StoreProduct')->find($row['cid']);
        }
        return $total_credit;
    }

    /**
     * 积分订单处理
     */
    public function show() {
        if (IS_POST) {
            $data = array();
            $data['openid'] = $openid = $this->openid;

            //快递配送
            $data['freight_type'] = I('post.pay_type', '1');

            $list = json_decode(I('post.list', '[]', 'trim'), true);
            foreach ($list as &$val) {
                $data['product_id'] = $val['cid'];
                $data['credit'] = $val['credit'];
                $data['product_params'] = $val['param'];
                $data['num'] = $val['num'];
                /* 快递配送 */
                $fid = I('post.freight', 0, 'intval');
                if (empty($fid)) {
                    //免邮处理
                    $data['freight'] = "免邮";
                    $data['pay_freight'] = "0";
                    $data['pay_status'] = 0;
                } else {
                    $deliverys = M("StoreDelivery")->where(array('id' => $fid))->find();
                    $data['freight'] = $deliverys['name'];
                    $data['pay_freight'] = $deliverys['firstprice'];

                    //支付类型
                    $data['pay_type'] = I("post.pay_type"); //默认是微信支付
                }
            }

            //总积分
            $data['total_credit'] = $this->_getTotal($list);

            //获取收货信息
            $data['accept_name'] = I("post.accept_name");
            $data['province'] = I("post.province");
            $data['city'] = I("post.city");
            $data['area'] = I("post.area");
            $data['address'] = I("post.address");
            $data['phone'] = I("post.phone");
            $data['postcode'] = I("post.postcode");

            // 用户留言
            $data['post_script'] = I('post.post_script');

            //判断用户积分是否足够兑换商品
            $member_credit = M('Member')->where(array('openid' => $this->openid))->getField('integral');
            if (intval($member_credit) < $data['total_credit']) {
                $this->error('您的可用积分不足，请选择其他商品');
            }

            //开启事务
            M()->startTrans();
            $result = $this->_save($data, D('StoreCredit'));

            if ($result['status']) {
                $result_list = true;
                //生成自定义兑换号
                $data['id'] = $result['data']['id'];
                //前面带上2 代表积分订单
                $data['credit_no'] = '2' . date('ymdH') . str_pad("{$result['data']['id']}", 4, '0', STR_PAD_LEFT);
                $result = $this->_save($data, D('StoreCredit'));

                //更新库存与销量
                $tmpParams = update_product_store(intval($data['product_id']), $data['num'], $data['product_params'], "credit");

                $r1 = M("StoreCreditProduct")->where(array('id' => intval($data['product_id'])))->setField('params', $tmpParams);
                $r2 = M('StoreCreditProduct')->where(array('id' => intval($data['product_id'])))->setInc('sale', $data['num']);

                //积分同步
                D('Shop/MemberIntegral')->changeIntegral($this->openid, -$data['total_credit'], "兑换商品使用", false);

                if ($r1 === FALSE or $r2 === FALSE) {
                    $result_list = false;
                }

                //提交表单
                if ($result_list) {
                    M()->commit();
                    $credit_id = $result['data']['id'];
                    $this->success('兑换成功', U('/shop/pay/credit_' . $credit_id));
                    die();
                }
            }
            M()->rollback();
            $this->error('兑换失败，请稍候再试！');
        } else {
            //获取积分订单信息
            $credit_id = I('get.credit_id', 0, 'intval');
            $creditInfo = M("StoreCredit")->where(array("id" => $credit_id))->find();

            //根据产品id获取产品信息
            $productInfo = M("StoreCreditProduct")->where(array("id" => intval($creditInfo['product_id'])))->find();
            $productInfo['num'] = $creditInfo['num'];
            $productInfo['credit'] = $creditInfo['credit'];
            $productInfo['params'] = $creditInfo['product_params'];

            $this->assign("product", $productInfo);
            $this->assign("info", $creditInfo);

            $this->assign("ptitle", "订单详情");
            $this->display();
        }
    }

    /**
     * 积分兑换记录
     */
    public function record() {
        parent::index(M("StoreCredit"));
    }

    /**
     * 积分兑换记录数据列表处理
     * @param type $data
     */
    protected function _record_data_filter(&$data) {
        foreach ($data as &$val) {
            $product = M("StoreCreditProduct")->field("name,img")->where(array("id" => $val['product_id']))->find();
            $val['product_name'] = $product['name'];
            $val['img'] = $product['img'];
        }
    }

    /**
     * 取消订单，status置1
     * @author luoshaobo <shao156324@sina.com>
     * @date 2015-5-6
     */
    public function cancel() {
        //获取要删除的id
        if (IS_POST) {
            $id = I('post.id', 0, "intval");
            if (!$id) {
                $this->error('数据参数错误');
            }

            $orderInfo = M("StoreCredit")->where(array('id' => $id))->find();

            //更新库存与销量
            $product = M("StoreCreditProduct")->field("id,params,sale")->where(array('id' => $orderInfo['product_id']))->find();
            $product['product_nums'] = $orderInfo['num'];
            //1、解析json格式的属性参数
            $tmpParams = json_decode($product['params'], true);
            M()->startTrans();
            if (!empty($tmpParams)) {
                //2、根据产品快照里的params与product_nums修改产品库存吧
                $str = $orderInfo['product_params'] . "_split_store";
                $tmpParams[$str] = $tmpParams[$str] + $product['product_nums'];
                //3、然后根据产品Id更新产品的params参数
                $tmpParams = json_encode($tmpParams);
                $r2 = M("StoreCreditProduct")->where(array('id' => $product['id']))->setField("params", $tmpParams);
            }

            if ($orderInfo['pay_status']) {
                //获取商城自动退款配置
                $refund = M("Store")->where(array("id" => 1))->getField("is_refund");
                if (!empty($refund)) {
                    //退钱
                    $r1 = D('Store/StoreCredit')->refund($id, $this->getInstanceWechatPay());
                } else {
                    $r1 = false;
                }
            }
            //更新订单状态
            if (empty($r1)) {
                $data = array();
                $data['cancel_date'] = get_now_date();
                $data['id'] = $id;
                $data['status'] = '3';
                $r1 = M("StoreCredit")->save($data);
            }

            //积分同步
            $r3 = D('Shop/MemberIntegral')->changeIntegral($this->openid, "--" . $orderInfo['total_credit'], "取消兑换返还积分", false);
            //订单积分归零
            M("StoreCredit")->where(array('id' => $orderInfo['id']))->setField('total_credit', 0);
            
            if (false !== $r1 && $r2 !== false && $r3 !== false) {
                M()->commit();
                $this->success("取消积分订单成功！", U("Shop/CreditOrder/index"));
            } else {
                M()->rollback();
                $this->error("取消积分订单失败，请稍候再试");
            }
        }
    }

}
