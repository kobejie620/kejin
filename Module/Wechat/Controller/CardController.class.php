<?php

namespace Wechat\Controller;

/**
 * 营销活动管理
 */
class CardController extends WechatController {

    /**
     * 绑定操作的模型
     * @var type 
     */
    protected $_bind_model = 'WechatCard';

    /**
     * 定义模型名称
     * @var type 
     */
    public $ptitle = '微信卡券';

    /**
     * 设定可访问的操作
     * @var type 
     */
    public $access = array(
        'index' => '卡券列表',
        'add' => '添加卡券',
        'edit' => '修改卡券',
        'del' => '删除卡券',
        'resume' => '启用',
        'forbid' => '禁用',
    );

    protected function _filter($model, &$map) {
        $map['status'] = array('neq', 3);
    }

    protected function _form_filter($model, $vo) {
        $wechat = $this->getInstanceWechat(); //生成微信类操作对象  
        $color = $wechat->getCardColors();   //获取卡券颜色
        if (IS_POST) {
            /* 将logo上传到微信 */
            $info = pathinfo($_POST['logo_url_show']);
            $filename = UPLOAD_PATH . 'card/' . $info['basename'];
            !is_dir(dirname($filename)) && mkdir(dirname($filename), 0777, true);
            if (file_put_contents($filename, file_get_contents($_POST['logo_url_show']))) {
                $result = $wechat->upLoadImg($filename);
                unlink($filename);
                ($result !== false) ? $local_logo_url = $result : $this->error('上传logo失败，请稍后再试');
            } else {
                $this->error('上传logo失败，请稍后再试');
            }
            $_POST['logo_url'] = $local_logo_url['url'];
            /* 将logo上传到微信 */
            if ($_POST['date_info_type'] == 'DATE_TYPE_FIX_TIME_RANGE') {
                $_POST['date_info'] = json_encode(array('type' => $_POST['date_info_type'], 'begin_timestamp' => strtotime($_POST['begin_timestamp']), 'end_timestamp' => strtotime($_POST['end_timestamp']) + 3600 * 24));
            } else {
                $_POST['date_info'] = json_encode(array('type' => $_POST['date_info_type'], 'fixed_begin_term' => $_POST['fixed_begin_term'], 'fixed_term' => $_POST['fixed_term']));
            }
            foreach ($color['colors'] as $key => $value) {
                ($value['name'] == $_POST['color']) && $color_value = $value['value'];   //确定卡券颜色和颜色代码
            }
            $_POST['color'] = json_encode(array($_POST['color'] => $color_value));


            /* 发送数据到微信创建卡券 */
            $card = array();
            $card['card_type'] = $_POST['card_type'];                              //卡券类型

            $base_info = array();
            $base_info['logo_url'] = $_POST['logo_url'];                           //卡券的商户logo，建议像素为300*300。
            $base_info['brand_name'] = $_POST['brand_name'];                       //商户名字,字数上限为12个汉字。
            $base_info['code_type'] = $_POST['code_type'];                         //Code展示类型，"CODE_TYPE_TEXT"，文本；"CODE_TYPE_BARCODE"，一维码 ；"CODE_TYPE_QRCODE"，二维码；"CODE_TYPE_ONLY_QRCODE",二维码无code显示；"CODE_TYPE_ONLY_BARCODE",一维码无code显示；
            $base_info['title'] = $_POST['title'];                                 //卡券标题
            $base_info['sub_title'] = $_POST['sub_title'];                         //副标题，字数上限为18个汉字。
            $base_color = json_decode($_POST['color'], true);
            $base_info['color'] = key($base_color);                                //券颜色
            $base_info['notice'] = $_POST['notice'];                                  //卡券使用提醒，字数上限为16个汉字。
            $base_info['service_phone'] = $_POST['service_phone'];                 //客服电话，非必填
            $base_info['description'] = $_POST['description'];                     //卡券使用说明，字数上限为1024个汉字。
            $base_info['date_info'] = json_decode($_POST['date_info'], true);            //使用日期，有效期的信息。
            $base_info['sku'] = array('quantity' => $_POST['quantity']);                //卡券库存的数量，不支持填写0，上限为100000000。
            $base_info['get_limit'] = $_POST['get_limit'];                              //每人可领券的数量限制,不填写默认为50。
            $base_info['use_custom_code'] = false;                                 //是否自定义Code码。填写true或false，默认为false，非必填
            $base_info['bind_openid'] = ($_POST['bind_openid'] == 'false') ? false : true; //是否指定用户领取，填写true或false。默认为false。通常指定特殊用户群体投放卡券或防止刷券时选择指定用户领取，非必填
            $base_info['can_share'] = ($_POST['can_share'] == 'false') ? false : true;     //卡券领取页面是否可分享，非必填
            $base_info['can_give_friend'] = ($_POST['can_give_friend'] == 'false') ? false : true;    //卡券是否可转赠，非必填
            $base_info['custom_url_name'] = $_POST['custom_url_name'];                  //自定义跳转外链的入口名字，非必填
            $base_info['custom_url'] = $_POST['custom_url'];                            //自定义跳转的URL，非必填
            $base_info['custom_url_sub_title'] = $_POST['custom_url_sub_title'];        //显示在入口右侧的提示语，非必填
            $base_info['promotion_url_name'] = $_POST['promotion_url_name'];            //营销场景的自定义入口名称，非必填
            $base_info['promotion_url'] = $_POST['promotion_url'];                      //入口跳转外链的地址链接，非必填
            $base_info['promotion_url_sub_title'] = $_POST['promotion_url_sub_title'];  //显示在营销入口右侧的提示语，非必填
            $base_info['source'] = $_POST['source'];                                    //第三方来源名，例如同程旅游、大众点评，非必填

            $lowercase_card_type = strtolower($card['card_type']);                   //小写的卡券类型
            $card[$lowercase_card_type]['base_info'] = $base_info;
            switch ($vo['card_type']) {
                case 'GROUPON': $card[$lowercase_card_type]['deal_detail'] = $_POST['deal_detail'];
                    break;
                case 'CASH': $card[$lowercase_card_type]['least_cost'] = $_POST['least_cost'];
                    $card[$lowercase_card_type]['reduce_cost'] = $_POST['reduce_cost'];
                    break;
                case 'DISCOUNT': $card[$lowercase_card_type]['discount'] = $_POST['discount'];
                    break;
                case 'GIFT': $card[$lowercase_card_type]['gift'] = $_POST['gift'];
                    break;
                case 'GENERAL_COUPON': $card[$lowercase_card_type]['default_detail'] = $_POST['default_detail'];
                    break;
            }
            $res = $wechat->createCard($card);
            if ($res['errcode'] == 0) {
                $_POST['card_id'] = $res['card_id'];
            } else {
                $this->error('网络繁忙，请稍后再试');
            }
            /* 发送数据到微信创建卡券 */
        } else {
            $fixed_term_day = array();
            for ($i = 1; $i <= 90; $i++) {
                $fixed_term_day[] = $i;
            }
            $this->assign('color', $color['colors']);
            $this->assign('fixed_term_day', $fixed_term_day);
        }
    }

    /**
     * 卡券详情
     */
    public function edit() {
        $model = D($this->_bind_model);
        if (IS_POST) {
            $card = array();
            $base_info = array();
            $base_info['service_phone'] = I('post.service_phone');                                  //客服电话。
            $base_info['custom_url_name'] = I('post.custom_url_name');                              //立即使用,自定义跳转入口的名字。
            $base_info['custom_url'] = I('post.custom_url');                                        //"xxxx.com",自定义跳转的URL。
            $base_info['custom_url_sub_title'] = I('post.custom_url_sub_title');                    //更多惊喜,显示在入口右侧的提示语。
            $base_info['promotion_url_name'] = I('post.promotion_url_name');                        //产品介绍,营销场景的自定义入口名称。
            $base_info['promotion_url'] = I('post.promotion_url');                                  //XXXX.com,入口跳转外链的地址链接。
            $base_info['promotion_url_sub_title'] = I('post.promotion_url_sub_title');              //卖场大优惠,显示在营销入口右侧的提示语。
            $base_info['code_type'] = I('post.code_type');                                          //Code码展示类型，"CODE_TYPE_TEXT"，文本；"CODE_TYPE_BARCODE"，一维码 ；"CODE_TYPE_QRCODE"，二维码；"CODE_TYPE_ONLY_QRCODE",二维码无code显示；"CODE_TYPE_ONLY_BARCODE",一维码无code显示；
            $base_info['get_limit'] = I('post.get_limit');                                          //每人可领券的数量限制。
            $base_info['can_share'] = (I('post.can_share') == 'true') ? true : false;               //卡券原生领取页面是否可分享。
            $base_info['can_give_friend'] = (I('post.can_give_friend') == 'true') ? true : false;   //卡券是否可转赠。

            $card['card_id'] = I('post.card_id');                                                   //卡券ID
            $card[strtolower(I('post.card_type'))]['base_info'] = $base_info;

            $model->startTrans();
            $model->create();
            $res = $model->save();
            if ($res !== false) {
                $wechat = $this->getInstanceWechat();                                                   //生成微信类操作对象  
                $response = $wechat->updateCard($card);                                                 //向接口发送修改卡券信息
                if ($response['errcode'] === 0) {
                    ($response['send_check'] === true) && $model->where(array('id' => $_POST['id']))->save(array('examine_status' => 'CARD_STATUS_NOT_VERIFY'));
                    $model->commit();
                    $this->success('信息修改成功');
                } else {
                    $model->rollback();
                    $this->success('信息修改失败');
                }
            } else {
                $model->rollback();
                $this->success('信息修改失败');
            }
        } else {
            $id = I('get.id');
            $list = $model->where(array('id' => $id))->find();
            empty($list) && $this->error('抱歉，访问的数据不存在！');
            $list['color'] = json_decode($list['color'], true);
            $list['date_info'] = json_decode($list['date_info'], true);
            $this->assign('ptitle', '卡券详情');
            $this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 删除卡券,在这里并非将卡券真正意义从数据库删除，将status状态置为3表示删除
     * 
     * @param Model $model 数据对象模型
     * @param array $map 数据模型查找规则
     */
    public function del() {
        if (IS_POST && IS_AJAX) {
            $model = D($this->_bind_model);
            $id = I('post.id');
            empty($id) && $this->error('删除失败，请稍候再试！CODE[10001]');
            $card = $model->where(array('id' => $id))->find();
            $wechat = $this->getInstanceWechat(); //生成微信类操作对象  
            $model->startTrans();
            $res = $model->where(array('id' => $id))->save(array('examine_status' => 'CARD_STATUS_DELETE', 'examine_status_time' => date('Y-m-d H;i:s', time()), 'status' => 3));
            if ($res !== false) {
                $response = $wechat->delCard($card['card_id']);
                if ($res['errcode'] == 0) {
                    $model->commit();
                    $this->success('删除成功！');
                } else {
                    $model->rollback();
                    $this->error('删除失败，请稍候再试！CODE[100003]');
                }
            } else {
                $this->error('删除失败，请稍候再试！CODE[100002]');
            }
        } else {
            $this->error('访问异常，可能是你的人品太差了 ~_~');
        }
    }

    /**
     * 修改卡券库存
     */
    public function quantity() {
        $model = D($this->_bind_model);
        $id = I('get.id');
        $card = $model->where(array('id' => $id))->find();
        if (IS_POST) {
            if (($card['examine_status'] == 'CARD_STATUS_VERIFY_OK') || ($card['examine_status'] == 'CARD_STATUS_DISPATCH')) {
                $status = I('post.quantity_status'); //更改库存状态，1为减少，2为增加
                $quantity = I('post.quantity');      //更改库存数量多少
                !is_numeric($quantity) && $this->error('更改库存数量必须为数字！');
                (empty($id) || empty($status) || empty($quantity)) && $this->error('网络繁忙，请稍后再试！');
                $wechat = $this->getInstanceWechat(); //生成微信类操作对象  
                $model->startTrans(); //开启事务
                if ($status == 1) {    //减少库存
                    ($quantity > $card['quantity']) && $this->error('库存不能少于1！');
                    $res = $model->where(array('id' => $id))->setDec('quantity', $quantity);
                } else {               //增加库存
                    $res = $model->where(array('id' => $id))->setInc('quantity', $quantity);
                }
                $data = array();
                if ($res !== false) {
                    $data['card_id'] = $card['card_id'];
                    ($status == 1) ? $data['reduce_stock_value'] = $quantity : $data['increase_stock_value'] = $quantity;
                    $response = $wechat->modifyCardStock($data);   //微信接口减少库存
                    if ($response['errcode'] == 0) {
                        $model->commit();
                        $this->success('修改成功');
                    } else {
                        $model->rollback();
                        $this->error($response['errmsg']);
                    }
                } else {
                    $model->rollback();
                    $this->error('网络繁忙，请稍后再试！');
                }
            } else {
                $this->error('卡券还未通过审核，无法使用此功能');
            }
        } else {
            empty($id) && $this->error('网络繁忙，请稍候再试！CODE[10001]');
            $this->assign('list', $card);
            $this->assign('ptitle', '修改卡券库存');
            $this->display();
        }
    }

}
