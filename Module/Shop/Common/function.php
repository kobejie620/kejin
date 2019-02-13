<?php

use Library\Util\Curl;

/**
 * ------------------
 * 商城部分函数
 * ------------------
 */

/**
 * 显示订单状态函数
 * 
 * @author Zoe<tanglinjun>
 * @date 2014-12-12
 */
function show_order_status($vo, $type = '') {
    //判断积分订单是否免邮
    if ($type == "credit") {
        $free = ($vo['pay_freight'] > 0) ? 1 : 0;
    }
    switch ($vo['status']) {
        case 1:
            if (isset($free)) {//积分订单
                if ($free == 1) {
                    $title = "待付款";
                } else {
                    $title = "待发货";
                }
            } else {//非积分订单
                $title = "待付款";
            }
            $class = "red";
            break;
        case 2:
            $title = "已支付";
            $class = "gray";
            break;
        case 3:
            $title = "已取消";
            if (intval($vo['pay_status']) === 1) {
                $title = '等待退款';
            }
            break;
        case 4:
            $title = "已退款";
            $class = "green";
            break;
        case 5:
            $title = "已完成";
            $class = "green";
    }
    return '<span class="ml20 ' . $class . '">' . $title . '</span>';
}

/**
 * 获取指定分类下面的所有子分类
 * @param1 $list 数据源
 * @param2 $pid   查询的分类pid
 * 
 * @author Zoe<tanglinjun>
 * @date 2014-12-19
 */
function get_children_ids($list, $pid) {
    $ids = array();
    static $i = 0;
    $i++;
    foreach ($list as $key => $vo) {
        //1、根据分类id获取记录
        if ($vo['pid'] == $pid) {
            $ids[] = $vo['id'];
            $sub = get_children_ids($list, $vo['id']);
            $ids = array_merge($ids, $sub);
        }
    }
    return $ids;
}

/**
 * 获取某商品某规则的库存
 * @param1 $pid 产品id
 * @param2 $param 所选择的产品属性 
 * 
 * @author tanglinjun
 * @date 2015-01-20
 */
function get_product_store($pid, $param) {
    //1、获取产品属性参数
    $productInfo = M('StoreProduct')->field("id,name,params")->where(array('id' => $pid))->find();
    //2、解析产品属性
    $params = json_decode($productInfo['params'], true);
    //3、获取产品库存
    $tempStr = $param . '_split_store';
    return $params[$tempStr];
}

/**
 * 更新某商品库存的方法
 * @param1 $pid 产品id
 * @param2 $num 购买数量
 * @param3 $property 产品属性
 * 
 * @author tanglinjun
 * @date 2015-01-23
 */
function update_product_store($pid, $num, $property, $type = "shop") {
    $tmpParams = array();
    //1、获取产品信息 如果是shop 则去正常商品； 如果是credit 则取积分商品
    if ($type == "shop") {
        $productInfo = M("StoreProduct")->where(array("id" => $pid))->find();
    } elseif ($type == "credit") {
        $productInfo = M("StoreCreditProduct")->where(array("id" => $pid))->find();
    }
    //2、获取全部属性
    $tmpParams = json_decode($productInfo['params'], true);
    //3、获取库存属性
    $tmpStr = $property . "_split_store";
    //4、原有库存-兑换数量
    $tmpParams[$tmpStr] = strval($tmpParams[$tmpStr] - $num);
    //5、重新编码json
    $tmpParams = json_encode($tmpParams);
    return $tmpParams;
}

/**
 * 根据x与y坐标
 * 
 */
function queryshops($myLocationX, $myLocationY) {
    $list = M('Outlet')->where(array('type' => '1', 'status' => '2'))->order('id DESC')->select();
    // 有用户位置信息,才计算最近的五家门店
    if ($myLocationX && $myLocationY) {
        foreach ($list as $key => $value) {
            // 单位为KM
            $location = explode(',', $value['latLng'], 2); //从数据库里查询出门店的经纬度
            $list [$key]['location_x'] = $location[0];
            $list [$key]['location_y'] = $location[1];
            $res = $list [$key] ['distance'] = getDistance($list [$key]['location_x'], $list [$key]['location_y'], $myLocationX, $myLocationY); //计算门店与用户的距离
            if ($res < 1) {
                $res = $res * 1000;
                $list [$key] ['distancetext'] = sprintf("%.2f", $res) . 'm';
            } else {
                $list [$key] ['distancetext'] = sprintf("%.2f", $res) . 'km';
            }
        }

        $newarray = array_sort($list, 'distance', 'asc');
        return array_slice($newarray, 0, 5);
    } else {
        // 无门店信息,查询所有
        return $list;
    }
}

/**
 * 计算两点之间的距离
 * 
 */
function getDistance($x1, $y1, $x2, $y2) {
    $radLat1 = rad($x1);
    $radLat2 = rad($x2);

    $a = rad($x1) - rad($x2);
    $b = rad($y1) - rad($y2);

    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $s = $s * 6378.137;
    $s = round($s * 10000) / 10000;
    return $s;
}

function rad($d) {
    return $d * pi() / 180.0;
}

/**
 * 数组排序
 * 
 * @param 源数组 $array        	
 * @param 排序的字段 $keys        	
 * @param 排序方式 $type        	
 * @return multitype:unknown
 */
function array_sort($arr, $keys, $type = 'desc') {
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue [$k] = $v [$keys];
    }
    if ($type == 'asc') {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    reset($keysvalue);
    foreach ($keysvalue as $k => $v) {
        $new_array [$k] = $arr [$k];
    }
    return $new_array;
}

/**
 * 获取运费区间
 * @param type $id
 * @return string
 */
function get_delivery_price($id, $type) {
    if ($type == "credit") {
        $free = M("StoreCreditProduct")->where(array("id" => $id))->getField("free_express");
    } else {
        $free = M("StoreProduct")->where(array("id" => $id))->getField("free_express");
    }
    if (!$free) {
        $info = "";
        $model = M("StoreDelivery");
        $map = array(
            "status" => 2
        );
        $max = $model->field("MAX(firstprice) as max_price")->where($map)->find();
        $min = $model->field("MIN(firstprice) as min_price")->where($map)->find();
        $info = "运费： " . $min['min_price'] . " - " . $max['max_price'] . " 元";
    } else {
        $info = "运费:免邮";
    }
    return $info;
}
