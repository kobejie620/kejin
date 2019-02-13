<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wap\Controller;

use Library\Util\Curl;
use Think\Verify;

C('SHOW_PAGE_TRACE', false);
C('SHOW_ERROR_MSG', false);

/**
 * Description of QueryContoller
 *
 * @author Anyon
 */
class QueryController extends WapController {

    /**
     * 防伪码查询
     */
    public function index() {
        $this->display();
    }

    /**
     * 检验报告
     */
    public function report() {
        if (IS_POST) {
            $code = I('post.code', '', 'trim');
            $this->assign('code', $code);
            if (empty($code)) {
                $this->assign('error', '产品生产日期不能为空');
            } else {
                $curl = new Curl();
                $_result = $curl->Post("http://yuanpei.xologood-fc.com/api/action.aspx", array('action' => 'get_trace', 'batch' => $code));
                $json = json_decode($_result, true);
                if (!empty($json['traceContent'])) {
                    $content = '';
                    foreach ($json['traceContent'] as $traceContent) {
                        $content.=strip_tags($traceContent['Content'], '<table><tr><td><div><p>');
                    }
                    $this->assign('result', $content);
                } else if (isset($json['msg'])) {
                    $this->assign('result', $json['msg']);
                } else {
                    $this->assign('result', '无结果');
                }
                $this->assign('code', $code);
                $this->assign('error', '');
            }
        }
        $this->assign('ptitle', '检验报告查询');
        $this->display();
    }

}
