<?php

namespace Wap\Controller;

/**
 * 店铺基础控制器类
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 13:59:00
 */
class StoreController extends WapController {

    public $gtitle = '商城管理';

    /*
     * 门店位置获取
     * @au/thor chenrongbin
     */

    public function index() {
        $myLocationX = I('get.latitude');
        $myLocationY = I('get.longitude');
//        $location = M('WechatMsg')->where(array('msgtype' => 'location' , 'fromusername' => $this->openid))->order('id DESC')->find();
//        $myLocationX = $location['location_x']; //纬度
//        $myLocationY = $location['location_y']; //经度
//        if(empty($location)) {
//            $location = M('WechatMsg')->where(array('event' => 'LOCATION' , 'fromusername' => $this->openid))->order('id DESC')->find();
//            $myLocationX = $location['latitude']; // 纬度
//            $myLocationY = $location['longitude']; // 经度
//        }
//        if(!($myLocationX && $myLocationY)) {  //判断是否有用户位置
////            $this->error("请发送您的位置");
////            redirect(U('Wap/Store/fail'));
//            $status = 1;
//        }
        if(!($myLocationX && $myLocationY)) {
            //$this->error("因为您没有获取位置，所以无法获取附近门店!");
            $this->error("请发送您的位置");
           // $this->redirect(U('Wap/Store/fail'));
        }

        $list = $this->queryshops($myLocationX, $myLocationY); //门店列表
        $this->assign("myLocationX", $myLocationX);
        $this->assign("myLocationY", $myLocationY);
        $this->assign("list", $list);
        $this->assign("tpltitle", "附近门店");
        $this->display();
    }

    /*
     * 获取位置失败
     */

    public function fail() {
        $this->assign("tpltitle", "获取位置失败");
        $this->display();
    }

    /*
     * 查询店铺
     */

    public function queryshops($myLocationX, $myLocationY) {
        $list = M('Stores')->order('id DESC')->select();
        // 有用户位置信息,才计算最近的五家门店
        if ($myLocationX && $myLocationY) {
            foreach ($list as $key => $value) {
                //单位为km
                $location = explode(',', $value['latlng'], 2); //从数据库中查询出门店的经纬度
                $list[$key]['location_x'] = $location[0];
                $list[$key]['location_y'] = $location[1];
                $res = $list[$key]['distance'] = $this->getDistance($list[$key]['location_x'], $list[$key]['location_y'], $myLocationX, $myLocationY); //计算门店与用户的距离
                if ($res < 1) {
                    $res = $res * 1000;
                    $list[$key]['distancetext'] = sprintf("%.2f", $res) . 'm';
                } else {
                    $list[$key]['distancetext'] = sprintf("%.2f", $res) . 'km';
                }
            }
            $newarray = $this->array_sort($list, 'distance', 'asc');
            return array_slice($newarray, 0, 10);
        } else {
            // 无门店信息,查询所有
            return $list;
        }
    }

    /**
     * 计算两点间的距离
     */
    private function getDistance($x1, $y1, $x2, $y2) {
        $radLat1 = $this->rad($x1);
        $radLat2 = $this->rad($x2);
        $a = $this->rad($x1) - $this->rad($x2);
        $b = $this->rad($y1) - $this->rad($y2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * 6378.137;
        $s = round($s * 10000) / 10000;
        return $s;
    }

    private function rad($d) {
        return $d * pi() / 180.0;
    }

    /**
     * 数组排序
     * @param 原数组$array
     * @param 排序的字段$key
     * @param 排序方法$type
     * @return multitype:unknown
     */
    private function array_sort($arr, $keys, $type = 'desc') {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }

    /**
     * 定位到地图
     */
    public function location() {
//        $location = M('WechatMsg')->where(array('msgtype' => 'location' , 'fromusername' => $this->openid))->order('id DESC')->find();
//        $myLocationX = $location['location_x']; // 纬度
//        $myLocationY = $location['location_y']; // 经度
        $myLocationX = I('get.my_x');
        $myLocationY = I('get.my_y');
        $myLocation = $myLocationX . ',' . $myLocationY;
        $toLocation = I("get.x") . ',' . I("get.y"); // x表示纬度,y表示经度
        $mapApiUrl = 'http://apis.map.qq.com/uri/v1/routeplan?type=bus&from=我的位置&fromcoord=' . $myLocation . '&to=附近的门店&tocoord=' . $toLocation . '&policy=1&referer=tengxun';
        redirect($mapApiUrl);
    }

}
