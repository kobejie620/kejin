<?php

namespace Library\Util;

use Think\Template\TagLib;

/**
 * Html标签库驱动
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2014/09/04 13:56:39
 */
class Wx extends TagLib {

    // 标签定义
    protected $tags = array(
        'table' => array(
            'attr'  => 'order,id,pk,class,color,bgcolor,action,show,sort,list,name,checkbox,length',
            'close' => 0
        ),
        'baidu' => array(
            'attr'  => 'key',
            'close' => 0
        ),
    );

    /**
     * 显示百度统计图片
     * 格式<tag:baidu key="" />
     * @param type $tag 标签属性
     * @return type
     */
    public function _baidu($tag) {
        $hm_key = empty($tag['key']) ? get_sysconfig('app_baidu_tongji_key') : $tag['key'];
        if (!empty($hm_key)) {
            $hm = new Baidu($hm_key);
            return '<img alt="百度统计CODE" src="' . $hm->trackPageView() . '" width="0" height="0" />' . "\n";
        }
    }

    /**
     * table标签解析
     * 格式： <tag:table list="" show="" />
     * @access public
     * @param string $tag 标签属性
     * @return string
     */
    public function _table($tag) {
        $id = empty($tag['id']) ? 'table_' . uniqid() : $tag['id']; //表格ID
        $pk = empty($tag['pk']) ? 'id' : $tag['pk']; //主键名,默认为id
        $list = $tag['list']; //列表显示的数据源VoList名称
        $class = $tag['class']; //样式名
        $name = !empty($tag['name']) ? $tag['name'] : 'vo'; //Vo对象名
        $attrs = empty($tag['attr']) ? false : explode(',', $tag['attr']); //添加额外的属性值
        $color = empty($tag['color']) ? false : $tag['color']; //设置行的字体颜色
        $bgColor = empty($tag['bgcolor']) ? false : $tag['bgcolor']; //设置行的背景颜色
        $sort = (isset($tag['sort']) && $tag['sort'] == 'true') ? true : false;
        $order = (isset($tag['order']) && $tag['order'] == 'true') ? true : false;
        $check = (isset($tag['check']) && $tag['check'] === 'false') ? false : ($tag['check'] && $tag['check'] !== 'true' ? $tag['check'] : 'key');
        $length = isset($tag['length']) ? $tag['length'] : false; // 输出列表长度

        /* 列表显示字段列表 */
        if (stripos($tag['show'], '$') === 0) {
            $show = $this->tpl->get(substr($tag['show'], 1));
        } elseif (stripos($tag['show'], '#') === 0) {
            $show = val(trim($tag['show'], '#'));
        } else {
            $show = $tag['show'];
        }
        $show = explode(',', $show);

        /* 功能列表分离 */
        $action = empty($tag['action']) ? false : explode(',', trim("{$tag['action']}"));


        /* 计算表格的列数 */
        $colNum = count($show);
        empty($check) || $colNum++;
        empty($action) || $colNum++;
        empty($order) || $colNum++;

        $hearStr = '<thead><tr>';
        /* 处理表格列头checkbox */
        empty($check) || $hearStr .='<th width="8"><input type="checkbox" id="check" data-checked-all="' . $id . '" data-checked-name="' . $check . '"></th>';
        empty($order) || $hearStr .='<th width="8"><button class="nowrap">排序</button></th>';

        $bodyStr = '<tbody>';
        /* 表格内容规则 */
        $lengthStr = !!$length ? "offset='0' length='{$length}'" : ''; // 支持输出部分数据
        $bodyStr .= "<notempty name='{$list}'><volist {$lengthStr} name='{$list}' id='{$name}'><tr ";
        if (!empty($color) || !empty($bgColor)) {
            $cssStyle = '';
            empty($color) || $cssStyle.="color:{\${$name}.{$color}};";
            empty($bgColor) || $cssStyle.="background-color:{\${$name}.{$bgColor}};";
            $bodyStr.="style='{$cssStyle}'";
            unset($cssStyle);
        }
        /* 设置表格第一行的TR属性值 */
        if (!empty($attrs)) {
            //# attr = 'id,title|phpfunc'
            foreach ($attrs as $v) {
                $item = strstr($v . '|', '|', true);
                $bodyStr.=" data-{$item}='{\${$name}.{$v}}'";
            }
        }
        $bodyStr .= '>';

        /* 如果需要显示checkbox 则在每行开头显示checkbox */
        empty($check) || $bodyStr .= "<td ><input type='checkbox' name='{$check}' value='{\${$name}.{$pk}}'></td>";
        empty($order) || $bodyStr .="<td><input style='width:50px;text-align:center;border:1px solid #ccc' name='order_pk_{\${$name}.{$pk}}' value='{\${$name}.sort|intval}' /></td>";

        /* 显示内容列#规则处理 */
        foreach ($show as $item) {
            //# 字段#path
            //# 字段:名称:对齐#PATH        -> SHOW 字段值 
            //# 字段|PHP函数:名称:对齐#PATH -> CALL_PHP函数(字段值,全记录数组,名称,PATH)
            //# 字段:JS函数:名称:对齐#PATH  -> CALL_JS函数(字段值,全记录对象,名称,PATH);
            list($rule, $path) = explode('#', "{$item}");
            $attr = explode(':', $rule);
            $last = end($attr);
            $align = end(explode('_', $last));
            if (in_array($align, array('left', 'center', 'right'))) {
                array_pop($attr);
                $last = end($attr);
            } else {
                $align = 'center';
            }
            $title = $last;
            $key = strstr($attr[0] . '|', '|', true);
            /* 显示字段表头 */
            $hearStr .= "<th style='text-algin:{$align}'>";
            if ($sort) {
                $order_select = ($key === I('get._order')) ? '<i class="glyphicon glyphicon-arrow-' . (I('get._sort', 0) ? 'up' : 'down') . '" style="color:#555"></i> ' : '';
                $url = url_filter(array('_order' => $key, '_sort' => intval(!I('get._sort', 0, 'intval'))));
                $hearStr.="<a href='javascript:void(0);' data-href='{$url}' data-title='null' title='按照 {$title} {\$_sort_type}'>{$order_select} {$title}</a>";
            } else {
                $hearStr .= $title;
            }
            $hearStr.='</th>';

            /* 显示表格内容 */
            $bodyStr.="<td style='text-align:{$align}'>";
            if (!Auth($path) || stripos($rule, '|') !== false) {
                $rule = array_shift($attr) . "=\${$name},'" . ( join(':', $attr) . "','{$path}'");
                $bodyStr.="{\${$name}.{$rule}}";
            } elseif (count($attr) < 3) {
                //# 字段#PATH
                //# 字段:名称#PATH
                $bodyStr .= "{\${$name}.{$key}}";
            } else {
                //# 字段:JS函数:名称#PATH  ---  JS函数(字段值,全记录对象,列头名称,PATH){}
                $bodyStr .= "<a href='javascript:void(0);' onclick='{$attr[1]}(\"{\${$name}.{$key}}\",\"\",\"{$title}\",\"{$path}\")'>{\${$name}.{$key}}</a>";
            }
            $bodyStr .= "</td>";
        }

        /* 列表操作项#规则处理 */
        if (!empty($action)) {
            //# 字段:名称:对齐#PATH        -> SHOW 字段值 
            //# 字段|PHP函数:名称:对齐#PATH -> CALL_PHP函数(字段值,全记录数组,名称,PATH)
            //# 字段:JS函数:名称:对齐#PATH  -> CALL_JS函数(字段值,全记录对象,名称,PATH);
            $bodyStr .= '<td class="tc nowrap">';
            foreach ($action as $val) {
                list($rule, $path) = explode('#', $val);
                $path = empty($path) ? 'null' : $path;
                if (!Auth($path)) {
                    continue;
                }
                $attr = explode(':', $rule);
                $key = strstr($attr[0] . '|', '|', true);
                $title = end($attr);
                if (strpos($val, '|') !== false) {
                    //# 字段|PHP函数:名称#PATH -> PHP函数(字段值,全记录数组,名称,PATH){}
                    //# 字段|PHP函数1|PHP函数2:名称#PATH
                    $rule = array_shift($attr) . "=\${$name},'" . ( join(':', $attr) . "','{$path}'");
                    $bodyStr.="{\${$name}.{$rule}} ";
                } elseif (count($attr) < 2) {
                    //# 字段:名称#PATH
                    $bodyStr .= "<a class='btn btn-xs btn-info' href='javascript:void(0);' >" . $title . "</a> ";
                } else {
                    $bodyStr .= "<a class='btn btn-xs btn-info' href='javascript:void(0);' onclick='{$attr[1]}(\"{\${$name}.{$key}}\",\"\",\"{$title}\",\"{$path}\")'>{$title}</a> ";
                }
            }
            $bodyStr .= '</td>';
        }

        empty($action) or $hearStr .= '<th >操作</th>';
        $hearStr .= '</tr></thead>';

        $bodyStr.="</tr></volist><else /><tr><td style='text-align:center;padding:10px;' colspan='{$colNum}'>{\$nodata|default='暂无数据'}</td></tr></notempty></tbody>";

        /* 拼接模板表格 */
        $parseStr = '';
        empty($order) || $parseStr.='<form onsubmit="return false;" data-ajax="true" data-progress="true" action="__SELF__?action=saveOrder" method="POST">';
        $parseStr .= "<table id='{$id}' class='{$class}' cellpadding=0 cellspacing=0 >";
        $parseStr.=$hearStr . $bodyStr;
        $parseStr .= '</table>';
        empty($order) or $parseStr.='</form>';
        return $parseStr;
    }

}
