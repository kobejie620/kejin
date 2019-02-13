<?php

namespace Apps\Controller;

/**
 * 有奖问答控制器
 * @author jindewen <jindewen@21cn.com>
 * @date 2015/03/31 15:51:47
 */
class SurveyController extends AppsController {

    /**
     * 绑定模型名
     * @var type
     */
    protected $_bind_model = 'AppsSurveyProblem';

    /**
     * 设置模块标题
     * @var type 
     */
    public $ptitle = "有奖问答";

    /**
     * 设置模块可访问的节点
     * @var type 
     */
    public $access = array(
        'index'     => '问题列表',
        'edit'      => '编辑问题',
        'borbid'    => '禁用问题',
        'resume'    => '启用问题',
        'del'       => '删除问题',
        'rule_edit' => '规则编辑',
        'record'    => '数据记录',
        'export'    => '数据导出',
    );

    /**
     * 设置可配置为菜单的节点
     * @var type 
     */
    public $menu = array(
        'index' => '问题列表'
    );

    protected function _index_filter(&$model, &$map) {
        /* 查询条件 */
        ($cid = I("get._cate")) != '' && $map['check_type'] = $cid;
        ($kw = I("get._kw")) && $map['title'] = array("like", "%$kw%");
    }

    public function rule_edit() {
        $_GET['id'] = 1;
        $this->_bind_model = 'AppsSurvey';
        $this->edit(null, null, 'Survey_rule_form');
    }

    /**
     * 微调研数据导出
     * Enter description here ...
     */
    public function export() {
        ini_set('memory_limit', '-1');
        $act_id = $_GET['act_id'];
        $survey = M('AppsSurveyProblem')->where(array('act_id' => $act_id))->order('id asc')->select();

        $fileName = '微调研信息-' . date('Y-m-d', time()) . '-' . uniqid();
        $PHPExcel = new PHPExcel();

        //设置基本信息
        $PHPExcel->getProperties()->setCreator("problem")
                ->setLastModifiedBy("problem")
                ->setTitle("微调研信息")
                ->setSubject("微调研信息")
                ->setDescription("")
                ->setKeywords("微调研信息")
                ->setCategory("");
        $PHPExcel->setActiveSheetIndex(0);
        $objActSheet = $PHPExcel->getActiveSheet();

        $objActSheet->setCellValue('A1', '问题');
        $objActSheet->setCellValue('B1', 'A选项答案');
        $objActSheet->setCellValue('C1', '选择A选项人数');
        $objActSheet->setCellValue('D1', 'B选项答案');
        $objActSheet->setCellValue('E1', '选择B选项人数');
        $objActSheet->setCellValue('F1', 'C选项答案');
        $objActSheet->setCellValue('G1', '选择C选项人数');
        $objActSheet->setCellValue('H1', 'D选项答案');
        $objActSheet->setCellValue('I1', '选择D选项人数');


        foreach ($survey as $key => $value) {
            $objActSheet->setCellValue('A' . ($key + 2), $value['title']);
            $objActSheet->setCellValue('B' . ($key + 2), $value['answer_a']);
            $objActSheet->setCellValue('C' . ($key + 2), $value['number_a']);
            $objActSheet->setCellValue('D' . ($key + 2), $value['answer_b']);
            $objActSheet->setCellValue('E' . ($key + 2), $value['number_b']);
            $objActSheet->setCellValue('F' . ($key + 2), $value['answer_c']);
            $objActSheet->setCellValue('G' . ($key + 2), $value['number_c']);
            $objActSheet->setCellValue('H' . ($key + 2), $value['answer_d']);
            $objActSheet->setCellValue('I' . ($key + 2), $value['number_d']);
        }

        //保存为2003格式
        $objWriter = new PHPExcel_Writer_Excel5($PHPExcel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");

        //多浏览器下兼容中文标题
        $encoded_filename = urlencode($fileName);
        $ua = $_SERVER["HTTP_USER_AGENT"];
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '.xls"');
        } else if (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $fileName . '.xls"');
        } else {
            header('Content-Disposition: attachment; filename="' . $fileName . '.xls"');
        }

        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit;
    }

    public function record() {
        $list = M('AppsSurveyRecord')->select();
        $this->assign('list', $list);
        $this->display();
    }

}
