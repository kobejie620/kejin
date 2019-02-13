<?php

namespace Plugin\Controller;

use Plugin\Library\QiniuList;
use Plugin\Library\Uploader;

C('SHOW_ERROR_MSG', false);
C('SHOW_PAGE_TRACE', false);
C('TMPL_TEMPLATE_SUFFIX', '.js');

/**
 * 百度编辑器插件
 * PS : 目录不能跨域操作
 */
class UeditorController extends PluginController {

    /**
     * 输出控制JS代码
     */
    public function index() {
        $this->__config();
        $this->assign('source_domin', $this->_getClientDomain());
        $this->display(null, 'utf-8', 'application/javascript');
    }

    /**
     * 七牛云服务
     */
    public function server() {
        $this->config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(dirname(dirname(__FILE__)) . "/Library/config.json")), true);
        switch (I('get.action', 'config', 'trim')) {
            case 'config': /* 读取配置文件 */
                $result = json_encode($this->config);
                break;
            case 'uploadimage': /* 上传图片 */
            case 'uploadscrawl': /* 上传涂鸦 */
            case 'uploadvideo':/* 上传视频 */
            case 'uploadfile': /* 上传文件 */
                $result = $this->_actionUpload();
                break;
            case 'listimage':/* 列出图片 */
            case 'listfile': /* 列出文件 */
                $result = $this->_actionList();
                break;
            /* 抓取远程文件 */
            case 'catchimage':
                $result = include("action_crawler.php");
                break;
            default:
                $result = json_encode(array(
                    'state' => '请求地址出错'
                ));
                break;
        }
        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state' => 'callback参数不合法'
                ));
            }
        } else {
            echo $result;
        }
    }

    /**
     * 生成七牛上传Token
     */
    public function getToken() {
        $this->__config();
        $accessKey = AKEY;
        $secretKey = SKEY;
        $bucket = BUCKET;
        $host = DOMAIN;
        $time = time() + 3600;
        if (empty($_GET["key"])) {
            exit('param error');
        } else {
            $data = array(
                "scope"      => $bucket . ":" . I('get.key', '', 'trim'),
                "deadline"   => $time,
                "returnBody" => "{\"url\":\"{$host}$(key)\", \"state\": \"SUCCESS\", \"name\": $(fname),\"size\": \"$(fsize)\",\"w\": \"$(imageInfo.width)\",\"h\": \"$(imageInfo.height)\"}"
            );
        }
        $find = array('+', '/');
        $replace = array('-', '_');
        $data = str_replace($find, $replace, base64_encode(json_encode($data)));
        $sign = hash_hmac('sha1', $data, $secretKey, true);
        $result = $accessKey . ':' . str_replace($find, $replace, base64_encode($sign)) . ':' . $data;
        echo $result;
    }

    /**
     * 上传操作
     */
    protected function _actionUpload() {
        /* 上传配置 */
        $base64 = "upload";
        switch (htmlspecialchars($_GET['action'])) {
            case 'uploadimage':
                $config = array(
                    "pathFormat" => $this->config['imagePathFormat'],
                    "maxSize"    => $this->config['imageMaxSize'],
                    "allowFiles" => $this->config['imageAllowFiles']
                );
                $fieldName = $this->config['imageFieldName'];
                break;
            case 'uploadscrawl':
                $config = array(
                    "pathFormat" => $this->config['scrawlPathFormat'],
                    "maxSize"    => $this->config['scrawlMaxSize'],
                    "allowFiles" => $this->config['scrawlAllowFiles'],
                    "oriName"    => "scrawl.png"
                );
                $fieldName = $this->config['scrawlFieldName'];
                $base64 = "base64";
                break;
            case 'uploadvideo':
                $config = array(
                    "pathFormat" => $this->config['videoPathFormat'],
                    "maxSize"    => $this->config['videoMaxSize'],
                    "allowFiles" => $this->config['videoAllowFiles']
                );
                $fieldName = $this->config['videoFieldName'];
                break;
            case 'uploadfile':
            default:
                $config = array(
                    "pathFormat" => $this->config['filePathFormat'],
                    "maxSize"    => $this->config['fileMaxSize'],
                    "allowFiles" => $this->config['fileAllowFiles']
                );
                $fieldName = $this->config['fileFieldName'];
                break;
        }
        /* 生成上传实例对象并完成上传 */
        $up = new Uploader($fieldName, $config, $base64);
        /* 返回数据 */
        return json_encode($up->getFileInfo());
    }

    /**
     * 列表操作
     */
    protected function _actionList() {
        $this->__config();
        /* 判断类型 */
        switch (I('get.action', '', 'trim')) {
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $this->config['fileManagerAllowFiles'];
                $listSize = $this->config['fileManagerListSize'];
                $path = $this->config['fileManagerListPath'];
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowFiles = $this->config['imageManagerAllowFiles'];
                $listSize = $this->config['imageManagerListSize'];
                $path = $this->config['imageManagerListPath'];
        }
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
        //var_dump($allowFiles);
        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $end = $start + $size;
        //演示方法
        $Qiniu_List = QiniuList::getInstance();
        $Qiniu_List->getUrl('', '', 1000);
        $files = $Qiniu_List->listFiles();
        $marker = $files['marker'];
        if (!count($files['items'])) {
            return json_encode(array(
                "state" => "no match file",
                "list"  => array(),
                "start" => $start,
                "total" => count($files)
            ));
        }
        /* 获取指定范围的列表 */
        $len = count($files['items']);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--) {
            if (preg_match("/\.($allowFiles)$/i", $files['items'][$i]['key'])) {
                $list[] = array("url" => DOMAIN . $files['items'][$i]['key']);
            }
        }
        /* 返回数据 */
        $result = json_encode(array(
            "state" => "SUCCESS",
            "list"  => $list,
            "start" => $start,
            "total" => count($files['items'])
        ));
        return $result;
    }

    /**
     * 抓取远程文件
     */
    protected function _actionCrawler() {
        /* 上传配置 */
        $config = array(
            "pathFormat" => $this->config['catcherPathFormat'],
            "maxSize"    => $this->config['catcherMaxSize'],
            "allowFiles" => $this->config['catcherAllowFiles'],
            "oriName"    => "remote.png"
        );
        $fieldName = $this->config['catcherFieldName'];
        /* 抓取远程图片 */
        $list = array();
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        foreach ($source as $imgUrl) {
            $item = new Uploader($imgUrl, $config, "remote");
            $info = $item->getFileInfo();
            array_push($list, array(
                "state"    => $info["state"],
                "url"      => $info["url"],
                "size"     => $info["size"],
                "title"    => htmlspecialchars($info["title"]),
                "original" => htmlspecialchars($info["original"]),
                "source"   => htmlspecialchars($imgUrl)
            ));
        }
        /* 返回抓取数据 */
        return json_encode(
                array(
                    'state' => count($list) ? 'SUCCESS' : 'ERROR',
                    'list'  => $list
                )
        );
    }

}
