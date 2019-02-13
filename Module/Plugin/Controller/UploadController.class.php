<?php

namespace Plugin\Controller;

use Library\Util\Qiniu;

C('SHOW_ERROR_MSG', false);
C('SHOW_PAGE_TRACE', false);
C('TMPL_TEMPLATE_SUFFIX', '.js');

/**
 * 七牛云存储
 */
class UploadController extends PluginController {

    /**
     * UE配置信息
     * @var type 
     */
    protected $config;

    /**
     * 输出控制JS代码
     */
    public function index() {
        $this->__config();
        $qiniu = new Qiniu(AKEY, SKEY);
        $upload_token = $qiniu->QiniuRSPutPolicy(BUCKET);
        $this->assign('upload_token', $upload_token);
        $this->assign('upload_domain', DOMAIN);
        $this->display(null, 'utf-8', 'application/javascript');
    }

}
