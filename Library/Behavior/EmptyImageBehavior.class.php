<?php

namespace Library\Behavior;

use Think\Behavior;

/**
 * 对异常图片进行处理
 * @author zoujingli<zoujingli@qq.com>
 */
class EmptyImageBehavior extends Behavior {

    /**
     * 行为执行入口
     * @param type $param
     */
    public function run(&$param) {
        if (C('HANDLER_IMG_ERROR') !== false) {
            $pattern = '|<img(.*?)>|i';
            $replace = '<img onerror="window.hook_image_error&&window.hook_image_error(this)" $1>';
            $param = preg_replace($pattern, $replace, $param);
        }
    }

}
