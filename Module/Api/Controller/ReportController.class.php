<?php

namespace Api\Controller;

use Think\Controller;

/**
 * @author chenrongbin <chenrongbin@qq.com>
 * @date 2018/11/13 10:40
 */
class ReportController extends Controller {


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
//        var_dump("fuck");
//        $idfa = I('get.idfa');
//        $ip = I('get.ip');
//        $appid = I('get.appid');
//        $callback = I('get.callback');
//    }

    /**
     * 广告追踪回调
     */
    public function report() {
        //http://guangggaozhu.com/click?callback={第三方回调地址}
        //http://disanfang.com/click?callback={渠道回调地址}
        $aff_id = I('get.affid') ? I('get.affid') : $this->error('affid不能为空!');  //渠道id
        $off_id = I('get.offid') ? I('get.offid') : $this->error('offid不能为空!');//广告id
        $click_ip = I('get.ip');//点击ip
        $idfa = I('get.idfa');// ios
        $callback = I('get.callback'); //回调渠道提供的url
        $user_agent = $_SERVER['HTTP_USER_AGENT']; //获取请求的user agent
        $request_url =(is_ssl() ? 'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; //获取请求的url
        $client_ip =  get_client_ip(); //获取请求的ip
        //判断当前广告、渠道是否开始接收数据,status 1 暂停，2 运行
        $map = array(
            'channel_id' => $aff_id,
            'ad_id' => $off_id,
            'status' => 2
        );
        $click_id = M('post_back')
                  ->where($map)
                  ->getField('click_id');
        if(!$click_id) $this->error('数据不合法!');

        //数据验证合法后，插入到点击明细表中wx_post_back_item
        $data = array(
            'click_id' => $click_id,
            'ad_id' => $off_id,
            'channel_id' => $aff_id,
            'click_ip' => $click_ip,
            'idfa' => $idfa,
            'callback' => $callback,
            'click_time' => date("Y-m-d H:i:s",time()),
            'click_ua' => $user_agent,
            'client_ip' => $client_ip,
            'click_url' => $request_url,
            'status' => 0,
            'create_time' => date("Y-m-d H:i:s",time())
        );
        $click_item_id = M("post_back_item")->data($data)->add();
        //点击数据保存成功，将数据上传给上游
        if($click_item_id){
            //获取上游提供的callback_url
            $post_back = M('ad')
                         ->where(array('ad_id' => $off_id))
                         ->getField('ad_track_link');
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
                '{callback}' =>'http://127.0.0.1/jonza/index.php/Api/Report/click?tid='.$click_item_id
            );
            /* 回调地址不为空 */
            if($post_back){
                $post_back = strtr($post_back, $post_data);//替换地址上的参数
                //填充参数后请求url

                $fp = fopen($post_back,'r');
                echo 'result:'.$post_back;
                $result='';
                while(!feof($fp)) {
                    $result .= fgets($fp, 1024);
                }
                echo '<br>请求上游返回的结果:'.$result;
                //请求成功后,更新post_back_item[offer_status] 的状态 1(默认为0表示未发送给上游，1表示已发送给上游)
                $click_item_id = M('post_back_item')
                               ->where(array('click_item_id' => $click_item_id))
                               ->save(array('offer_status' => 1,'offer_url'=>$post_back));
                echo '<br>更新上游状态成功：'.$click_item_id;
            }
        }

    }
    
    /**
     *接收第三方传给上游的数据。这是一个模拟上游接收第三方数据的方法。
     */
    public function receive(){
        echo '<br>url:'.(is_ssl() ? 'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        //上游收到第三方数据后，把转化数据根据第三方的callback推回数据回去,因为是模拟的。所以是百分百转化
        $aff_id = I('get.affid') ? I('get.affid') : $this->error('affid不能为空!');  //渠道id
        $off_id = I('get.offid') ? I('get.offid') : $this->error('offid不能为空!');//广告id
        $idfa = I('get.idfa');// ios
        $click_url = (is_ssl() ? 'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $data = array(
            'channel_id' => $aff_id,
            'ad_id' => $off_id,
            'idfa' => $idfa,
            'click_url' => $click_url,
            'add_time'=> date("Y-m-d H:i:s",time()),
            'status'=> 0
        );
        //查找数据是否重复，数据重复表示转化失败
        $offer_item_id = M("offer_item")
                         ->where(array('channel_id'=>$aff_id,'ad_id'=>$off_id,'idfa'=>$idfa))
                         ->getField('offer_item_id');
        echo '<br>$offer_item_id:'.$offer_item_id.'参数值:'.'channel_id:'.$aff_id.'ad_id:'.$off_id.'idfa:'.$idfa;
        if(!$offer_item_id){
           //数据不重复，表示有效，加入到表中
            $offer_item_id = M("offer_item")->data($data)->add();
            if($offer_item_id){ //成功插入后回调下游的callback
                $callback = I('get.callback');
                $fp = fopen($callback,'r');
                echo '<br>上游返回的转化结果:'.$callback;
                $result='';
                while(!feof($fp)) {
                    $result .= fgets($fp, 1024);
                }
                echo  '<br>'.$result;
                return;
            }
        }
        echo '<br>:{"ret":-1,"msg":"失败提示信息"}';
    }
    /**
     *接收第上游返回的转化数据
     */
    public function click(){
        //根据返回的tid，去查数据，然后更新到转化明细表中
        $click_item_id = I('get.tid');
        echo '接收上游返回的转化数据！'.$click_item_id;

        $result = M('post_back_item')
                         ->where(array('click_item_id' => $click_item_id))
                         ->getField('click_item_id,ad_id,channel_id,callback');
        //数据有效，更新转化信息
        if($result){
            $aff_id = $result[$click_item_id]['channel_id'] ;  //渠道id
            $off_id = $result[$click_item_id]['ad_id'] ;//广告id
            $channel_callback = $result[$click_item_id]['callback'] ;//渠道的回调URL
            $offer_click_url = (is_ssl() ? 'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            //查找当前渠道，这个广告已经有多少转化数据
            $conversion_count = M('post_back_item')
                                ->where(array('ad_id' => $off_id,'channel_id' => $aff_id,'status'=>0,'conversion_status'=>1))
                                ->count();
            $rate = 0.1;//默认扣量比例为0.1
            $num = $rate * 100-1; //需要开始扣量的数值
            $remainder  =  $conversion_count%$num; //求余
            echo '<br>扣量：$remainder:'.$remainder.$channel_callback;
            if($remainder==0 &&$conversion_count>0){ // 能除尽，表示要扣量
                $deduction_status = 1;//1 表示扣量
                $channel_status = 0; //表示转化数据没有推给下游
            }else{  //不能除尽，还不能扣量
                $deduction_status = 0;//0 表示扣量
                $channel_status = 1;//1 表示把转化数据推给了下游
                //把转化数据推回给渠道
                $fp = fopen($channel_callback,'r');
                $result='';
                while(!feof($fp)) {
                    $result .= fgets($fp, 1024);
                }
                echo '下游返回结果:'.$result;
            }
            //更新转化信息
            $click_item_id = M('post_back_item')
                ->where(array('click_item_id' => $click_item_id))
                ->save(array('conversion_status' => 1,'offer_click_url'=>$offer_click_url,'deduction_status'=>$deduction_status,
                'channel_status'=>$channel_status));
        }
    }

    /**
     * 模拟下游接收数据
     */
    public function channel(){
        echo '下游收到转化数据！';
    }
}
