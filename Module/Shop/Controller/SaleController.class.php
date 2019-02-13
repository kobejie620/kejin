<?php

namespace Shop\Controller;

/**
 * 抢购商城的商品列表页
 * 
 * @author zenglingwen <630118900@qq.com>
 * @date 2014/09/22 10:54:32
 */
class SaleController extends ShopController {

	/**
	 * 绑定对应的模型名
	 * @var type 
	 */
	protected $_bind_model = 'StoreProduct';

	protected function _index_filter($model, &$map) {
		$info = M("Store")->find(1);
		$info['link'] = explode('|', "{$info['link']}");
		if (!empty($info['url'])) {
			redirect($info['url']);
		}
		$img = json_decode($info['xs_content']);
		$this->assign('info', $info);
		$this->assign('llimg', $img);
		$map['xianshi'] = array('eq', 1); /* 限时抢购商品的筛选 */
		$time = date("Y-m-d", time());
		$map['xs_start_time'] = array('like', '%' . $time . '%');
	}

	protected function _data_filter($data, $model) {
		foreach ($data as &$val) {
			$where['xianshi'] = array('eq', 1);
			$time = date("Y-m-d", strtotime("+1 day"));
			$where['xs_start_time'] = array('like', '%' . $time . '%');
			$morgen_info = M('StoreProduct')->where($where)->select();
			$this->assign('morgen_info', $morgen_info);
		}
	}

}
