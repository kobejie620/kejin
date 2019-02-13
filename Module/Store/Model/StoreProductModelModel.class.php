<?php

namespace Store\Model;

/**
 * 产品模型管理模型
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 14:55:02
 */
class StoreProductModelModel extends StoreModel {

    /**
     * 解析产品模型参数
     * @param type $model_params
     * @return type
     */
    public function parseParams($model_params) {
        $_model_params = json_decode($model_params, true);
        $model_params_array = array();
        foreach ($_model_params as $key => $item) {
            $arr = explode('_split_', $key);
            $model_params_array[$arr[0]][] = $arr[1];
        }
        return $model_params_array;
    }

}
