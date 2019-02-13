<?php

namespace Api\Controller;

use Think\Controller;

/**
 * @author chenrongbin <chenrongbin@qq.com>
 * @date 2018/11/13 10:40
 */
class DataController extends Controller {


//    public function report() {
//        $url = 'http%3A%2F%2Fals.baidu.com%2Fcb%2Fact
//ionCb%3Fa_type%3D%7B%7BATYPE%7D%7D%26a_value%3D%7B%7BAVALUE%7D%7D%26s%3D147886275081540234%2
//6o%3D1528878685000%26ext_info%3D9uY0EDhFfhkqEZQbDk3xWffexKWutNzogbRaTGPF1FVCqnO%252F8Wq%252F
//vvwGFgrUCytRbLy9RKkq0Z89vXxCQ7tMDtQgsTcIr7qJLfPYVM2SHZhcdgLUDp6nDXaM2YZxLOMEdL1oxxpNx5TO4oW8
//4wuv%252BGNom30%252B2YA2EB%252F40sOIZNV65DSAkP15OPFU0681RsEoJUL8mCHwd43EjYZ3S230mKV%252FIFqD
//LXBV3acsf8ZfMdS0gYxEx%252F1CKqTjluHxdUcK7EXHjjT7hWyQJ3pyb56fT2BvWNh%252FGy%252Fu6j9FSwmGYyK0
//fdOnKl9md2AMlBcm6KhXBa8xotra25u5Qbpktthwq53%252BokPRMZzkMy7aUB7pTJKygFE7VjL7IQjvdvfAhrb%252F
//YvsR2njRgzNIWnAH4n%252Fdk6k%252FCZqinR1WvKgeCU%252BIXB5c5Vkq2yIZtA%253D%253D';
//        p(urldecode($url));die;
////       (广告主_上家)http://report.etjg.com/report/index.php?idfa={idfa}&ip={ip}&appid=100618&companykey=codrim&callback={callback}
////       (渠道_下家)http://api.17ads.net/click?affid=89702&offid=5853522&track=t1&idfa={idfa}&ip={$ip}&callback={$callback}
///        affid_渠道id,offid_广告id
//    }

    /**
     * 获取从渠道传过来的数据
     */
    public function report() {
        //http://guangggaozhu.com/click?callback={第三方回调地址}
        //http://disanfang.com/click?callback={渠道回调地址}
        /* 渠道回发id */
        $click_id = I('get.click_id') ? I('get.click_id') : $this->response('400','click_id不能为空!');  //渠道回发id
        /* 获取渠道回发信息 */
        $info = M('PostBack')
            ->alias('pb')
            ->field('pb.*,a.ad_track_link')
            ->where(array('click_id' => $click_id))
            ->join('left join __AD__ a on pb.ad_id = a.ad_id')
            ->find();
        if(!$info) $this->response(400,'该渠道回发不存在');
        $aff_id = I('get.affid') ? I('get.affid') : '';  //渠道id
        $off_id = I('get.offid') ? I('get.offid') : '';//广告id
        $click_ip = I('get.ip') ? I('get.ip') : $this->response('400','ip不能为空!');   //点击ip
        $idfa = I('get.idfa') ? I('get.click_id') : $this->response('400','idfa不能为空!');  // 唯一标识符
        $client_ip =  get_client_ip(); //获取请求的ip
        /* 判断该idfa是否已经点击过了 */
        $where = ['idfa' => $idfa];
        $update_data = array();
        $is_idfa = $this->_isExistForDb(M("PostBackItem"), $data, $where);
        if(!$is_idfa) $update_data['similarity_click_num'] = array('exp','similarity_click_num+1');
        /* 添加点击明细 就是把idfa,ip那些插入到表中 */
        $data = array(
            'click_id' => $click_id,
            'ad_id' => $info['ad_id'],
            'channel_id' => $info['channel_id'],
            'click_ip' => $click_ip,
            'click_time' => time(),
            'idfa' => $idfa,
            'create_time' => time(),
        );
        $flag = M("PostBackItem")->data($data)->add();
        if($flag) {
            /* 点击数+1 */
            $update_data['click_num'] = array('exp','click_num+1');
            M('PostBack')->where(array('click_id' => $click_id))->save($update_data);
        }
        /* 然后再把数据传到广告主的ad_track_link上 */
        /* 参数	备注
        {tid}	17ADS标识每一次点击的唯一参数，当使用的回调方式不是callback时，该参数必须配置到Offer URL里面
        {advid}	17ADS广告主ID
        {affid}	17ADS渠道ID
        {offerid}	17ADS广告ID
        {sorceid}	子渠道标识，有17ADS渠道在tracking link里传其下游渠道标识
        {ts}	点击时间戳
        {sub1}	自定义参数，由渠道传参
        {sub2}	自定义参数，由渠道传参
        {sub3}	自定义参数，由渠道传参
        {sub4}	自定义参数，由渠道传参
        {sub5}	自定义参数，由渠道传参
        {sub6}	自定义参数，由渠道传参
        {sub7}	自定义参数，由渠道传参
        {sub8}	自定义参数，由渠道传参
        {sub9}	自定义参数，由渠道传参
        {sub10}	自定义参数，由渠道传参
        {idfa}	iOS设备唯一标识，由渠道传参
        {andid}	Android设备其中一个唯一标识，由渠道传参
        {imei}	Android设备其中一个唯一标识，由渠道传参
        {mac}	Android设备其中一个唯一标识，由渠道传参
        {os}	系统，如iOS或Android，由渠道传参
        {osver}	系统版本，由渠道传参
        {devmodel}	设备型号，由渠道传参
        {ip}	客户端ip,由渠道传参
        {client_ip}	17ADS 从渠道tracking link解析出的点击IP
        {callback}	回调地址，使用时，17ADS将回调地址作为参数值，encode之后在，作为参数传递。上游广告主需要在用户产生转化时，调用该回调地址
        广告追踪链接*/
        $post_data = array(
            '{tid}' => $click_id,
            '{advid}' => 1,
            '{affid}' => $aff_id,
            '{offerid}' => $off_id,
            '{sorceid}' => '',
            '{ts}' => date("Y-m-d H:i:s",time()),
            '{sub1}' => I('get.sub1'),
            '{sub2}' => I('get.sub2'),
            '{sub3}' => I('get.sub3'),
            '{sub4}' => I('get.sub4'),
            '{sub5}' => I('get.sub5'),
            '{sub6}' => I('get.sub6'),
            '{sub7}' => I('get.sub7'),
            '{sub8}' => I('get.sub8'),
            '{sub9}' => I('get.sub9'),
            '{sub10}' => I('get.sub10'),
            '{idfa}' => $idfa,
            '{andid}' => '',
            '{imei}' => '',
            '{mac}' => '',
            '{os}' => 'ios',
            '{osver}' => '',
            '{devmodel}' => '',
            '{ip}' => $click_ip,
            '{client_ip}' => $client_ip,
            //回调url,不知道怎么取，暂时写死(is_ssl() ? 'https://':'http://').$_SERVER['HTTP_HOST'].'/Api/Report/click?tid='.$click_item_id
//            '{callback}' =>'http://127.0.0.1/jonza/index.php/Api/Report/click?tid='.$click_item_id
        );
        $ad_track_link = strtr($info['ad_track_link'],$post_data);
        $this->http_get($ad_track_link);
    }

    /**
     * 接受从广告主传回来的回调
     */
    public function receive() {
        /* 统计一下转化率 */
        //http://disanfang.com/click?callback={渠道回调地址}
        /* 查一下该回发有没有做扣量，有扣量，扣量再返回给渠道，没扣量，直接返回给渠道（通过渠道回调地址） */
    }
}
