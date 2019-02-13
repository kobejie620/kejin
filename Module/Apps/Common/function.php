<?php

/* * * * * * * * * * * * * * * * * * * * * *
 * * * * OH, NO BUG NO BUG, OH YEAH! * * * *
 * * * * * * * * * * * * * * * * * * * * * *
 * @author luoshaobo <shao156324@sina.com> *
 * @date 2015-4-22 10:27:01* * * * * * * * *
 * * * * * * * * * * * * * * * * * * * * * * 
 */

/**
 * 获取问题类型
 * @param string $type_id 类型id
 * @return string
 */
function get_problem_type($type_id) {
    $types = array('单选', '多选');
    return $types[$type_id];
}

/**
 * 获取问题回答情况
 * @param string $title 标题
 * @param array $vo 全数组记录
 * @return string
 */
function get_answer_situation($title, $vo) {
    $letter = array('a', 'b', 'c', 'd', 'e', 'f');
    $stringBuilder = array('<b>' . $title . '</b>');
    $format = '<br/>%s:%s&nbsp;&nbsp;&nbsp;数量:%s';
    foreach ($letter as $val) {
        if ($vo['answer_' . $val] != '') {
            array_push($stringBuilder, sprintf($format, strtoupper($val), $vo['answer_' . $val], $vo['number_' . $val]));
        } else {
            break;
        }
    }
    return join('', $stringBuilder);
}

/**
 * 显示图片
 * @param type $url
 * @return type
 */
function show_image_link($url) {
    $arr = explode(',', $url);
    foreach ($arr as &$vo) {
        $vo = to_domain($vo);
        $extension = pathinfo($vo, PATHINFO_EXTENSION);
        if (empty($extension) || !in_array(strtolower($extension), array('bmp', 'png', 'jpge', 'jpg', 'gif'))) {
            $vo .= "?_file_=default.jpg";
        }
    }
    $srcs = join('|', $arr);
    $src = array_shift($arr);
    return "<img class='fancy img mr5' data-src='{$srcs}' style='height:22px' src='{$src}'/>";
}

/**
 * 格式化时间
 * @param type $timestamp
 * @return type
 */
function to_my_date($timestamp) {
    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * 显示照片评比的审核状态
 * @param type $check
 * @return string
 */

function show_picture_status($check){
    switch ($check){
        case 1:
            return "未审核";
        case 2:
            return "已审核";
    }
}

/*
 * 推广员列表详情
 */
function show_promotion_info($id) {
    $url = U('Apps/Promotion/details', array("id" => $id));
    return "<a class='btn btn-xs btn-link' data-load='{$url}'>详情</a>";
}
