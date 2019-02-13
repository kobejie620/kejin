<?php

namespace Library\Util;

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Excel2007;
use PHPExcel_Reader_Excel5;
use PHPExcel_Worksheet;

class Excel {

    /**
     * Excel操作对象
     * @var PHPExcel
     */
    protected $excel;

    /**
     * 
     * @var PHPExcel_Worksheet
     */
    protected $activeSheet;

    /**
     * Excel表格数据
     * @var PHPExcel 
     */
    protected $_tables = array();

    /**
     * 构建函数
     */
    public function __construct() {
        Vendor('PHPExcel.PHPExcel');
        Vendor('PHPExcel.IOFactory');
    }

    /**
     * 应用数据到工作表格中
     * @param type $data
     * @return Excel
     */
    public function renderData($data) {
        empty($this->activeSheet) && $this->createExcel();
        $row = 1;
        foreach ($data as $key => $vo) {
            if (is_string($key)) {
                $this->activeSheet->setCellValue($key, $this->_filterValue($vo, $key));
                continue;
            }
            if (is_array($vo)) {
                $col = 0;
                foreach ($vo as $v) {
                    $offset = $this->IntToChr($col++) . "{$row}";
                    $this->activeSheet->setCellValueExplicit($offset, $this->_filterValue($v, $offset), 's');
                    $this->activeSheet->getStyle()->getNumberFormat()->setFormatCode("@");
                }
                $row++;
            }
        }
        return $this;
    }

    /**
     * 过滤单元内容及设置值
     * @param type $value
     */
    private function _filterValue($value, $offset) {
        if (is_array($value)) {
            $_value = isset($value['value']) ? $value['value'] : $value[0];
            $format = isset($value['format']) ? $value['format'] : $value[1];
            $cell = $this->activeSheet->getCell($offset);
            $this->_applyFormat($cell, $format, $offset);
            return $_value;
        }
        return $value;
    }

    /**
     * 应用样式规则到表格
     * @param \Cell $cell
     * @param type $format
     */
    private function _applyFormat($cell, $format, $offset = '') {
        $params = explode(';', $format);
        $style = $this->activeSheet->getStyle($offset);
        foreach ($params as $param) {
            list($name, $value) = explode(':', $param);
            switch (strtolower(trim($name))) {
                case 'xalign':
                case 'text-align':
                    $style->getAlignment()->setHorizontal($value);
                    break;
                case 'yalign':
                case 'vertical-align':
                    $style->getAlignment()->setVertical($value);
                    break;
                case 'size':
                case 'font-size':
                    $style->getFont()->setSize(intval($value));
                    break;
                case 'font-weight':
                    (strtolower($value) === 'bold' || intval($value) > 500) ?
                                    $style->getFont()->setBold(true) :
                                    $style->getFont()->setBold(false);
                    break;
                case 'font':
                    $style->getFont()->setName($value);
                    break;
                case 'color':
                    $style->getFont()->getColor()->setRGB($value);
                    break;
                case 'full':
                    $style->getFill()->setFillType('solid');
                    $style->getFill()->getStartColor()->setRGB($value);
                    break;
                case 'width':
                    $this->activeSheet->getColumnDimension(preg_replace('/[0-9]/', '', $offset))->setWidth($value);
                    break;
                case 'height':
                    $this->activeSheet->getRowDimension(preg_replace('/[a-z]/i', '', $offset))->setRowHeight(doubleval($value));
                    break;
                case 'type':
                    switch (strtolower($value)) {
                        case 'string':
                            $value = 's';
                            break;
                        case 'number':
                            $value = 'n';
                    }
                    $this->activeSheet->setCellValueExplicit($offset, $cell->getValue(), $value);
                    $style->getNumberFormat()->setFormatCode("@");
            }
        }
    }

    /**
     * 设置当前工作表格
     * @param type $index 表格索引
     * @param type $name 表格名称
     */
    public function setSheet($index = 0, $name = '') {
        empty($this->excel) && $this->createExcel();
        if ($this->excel->getSheetCount() <= $index) {
            $this->excel->createSheet($index);
        }
        $this->activeSheet = $this->excel->setActiveSheetIndex($index);
        empty($name) || $this->activeSheet->setTitle($name);
        return $this;
    }

    /**
     * 创建一个新的工作簿
     * @return Excel
     */
    public function createExcel() {
        $this->excel = new PHPExcel();
        $this->setSheet();
        return $this;
    }

    /**
     * 读取excel
     * @param string $filename excel路径
     * @param numbwr $sheet 读取的工作表
     * @param number $max_column 读取的列数
     */
    public function readerExcel($filename, $sheet = 0, $max_column = 0) {
        $data = array();
        //默认用excel2007读取excel,若格式 不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filename)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filename)) {
                //返回空的数组
                return $data;
            }
        }
        //载入excel文件
        $this->excel = $PHPReader->load($filename);
        //获取工作表总数
        $sheetCount = $this->excel->getSheetCount();
        //判断是否超过工作表总数，取最小值
        $sheet = $sheet < $sheetCount ? $sheet : $sheetCount;
        //默认读取excel文件中的第一个工作表
        $currentSheet = $this->excel->getSheet($sheet);
        if (empty($max_column)) {
            //取得最大列号，这里输出的是大写的英文字母，ord()函数将字符转为十进制，65代表A
            $max_column = ord($currentSheet->getHighestColumn()) - 65 + 1;
        }
        //取得一共多少行
        $allRow = $currentSheet->getHighestRow();
        //从第二行开始输出，因为excel表中第一行为列名
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            for ($currentColumn = 0; $currentColumn <= $max_column - 1; $currentColumn++) {
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                $data[$currentRow - 2][$currentColumn] = $val;
            }
        }
        //返回二维数组
        return $data;
    }

    /**
     * 读取Excel模板或文件
     * @param type $filename Excel文件名
     * @return Excel
     */
    protected function readExcel($filename) {
        $this->excel = PHPExcel_IOFactory::load($filename);
        $this->setSheet();
        return $this;
    }

    /**
     * 直接下载Excel文件
     * @param type $fileName
     */
    public function download($fileName) {
        $this->excel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007');
        $objWriter->save('php://output');
        die();
    }

    /**
     * 保存Excel
     * @param type $filename
     */
    public function save($filename) {
        $this->excel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007');
        $objWriter->save($filename);
    }

    /**
     * 数字转字母 （类似于Excel列标）
     * @param Int $index 索引值
     * @param Int $start 字母起始值
     * @return string 返回字母
     * @author Anyon Zou <Anyon@139.com>
     * @date 2013-08-15 20:18
     */
    private function IntToChr($index, $start = 65) {
        $str = '';
        if (floor($index / 26) > 0) :
            $str .= IntToChr(floor($index / 26) - 1);
        endif;
        return $str . chr($index % 26 + $start);
    }

}
