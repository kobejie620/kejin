<?php

namespace Shop\Controller;

/**
 * 商品秒杀控制器
 *
 * @author Administrator
 */
class SeckillController extends ShopController {

	/**
	 * 绑定对应的模型类
	 * @var type 
	 */
	public $_bind_model = "StoreProduct";

	/**
	 * 定义可访问的方法
	 */
	public $access = array(
		'index' => '商品秒杀列表'
	);

	/**
	 * 显示时间最近的秒杀商品
	 * @param type $model
	 * @param type $map
	 */
	public function index() {
		$info = M("Store")->find(1);
		$info['link'] = explode('|', "{$info['link']}");
		if (!empty($info['url'])) {
			redirect($info['url']);
		}
		$img = json_decode($info['xs_content']);
		$this->assign('info', $info);
		$this->assign('llimg', $img);
		$map['miaosha'] = array('eq', 1); /* 商品秒杀的筛选 */
		$time = date("Y-m-d", time());
		$map['ms_end_time'] = array(array('like', '%' . $time . '%'),array('egt', date('Y-m-d H:i:s',time())));
		$list = M('StoreProduct')->field('s.*,m.name as model_name,s.params as model_params')->join('LEFT JOIN __STORE_PRODUCT_MODEL__ m ON m.id = s.model_id')
						->alias('s')->where($map)->order('ms_start_time')->limit(1)->find(); // 根据时间查询第一条记录
		$productModel = D('Store/StoreProductModel');
		$list['model_params'] = $productModel->parseParams($list['model_params']);
//		var_dump($list);
		$this->assign('vo', $list);
		$this->display();
	}

}
