<?PHP

/**
 * 生成关键字二维码
 * @param type $keys
 * @return type
 */
function create_qrc($keys) {
    if (!empty($keys)) {
        $map = array();
        $map['keys'] = $keys;
        $map['qrc'] = array(array('neq', ''), array('exp', 'is not null'), 'and');
        $url = M('WechatKeys')->where($map)->getField('qrc');
        if (!empty($url)) {
            return $keys . " <img class='fancy' data-src='{$url}&_name=qrc.jpg' style='height:14px' src='{$url}&_name=qrc.jpg'/>";
        }
        if (auth('Wechat/Keys/createQrc')) {
            $url = U('Wechat/Keys/createQrc', array('keys' => $keys));
            return $keys . " <a class='fr' href='javascript:void(0)' title='生成二维码' data-load='{$url}'><i class='glyphicon glyphicon-qrcode'></i></a>";
        }
    }
    return $keys;
}
