<?php

// 数组保存到文件
function arr2file($filename, $arr = '') {
    if (is_array($arr)) {
        $con = var_export($arr, true);
    } else {
        $con = $arr;
    }
    $con = "<?php\nreturn $con;\n?>"; //\n!defined('IN_MP') && die();\nreturn $con;\n
    write_file($filename, $con);
}

function write_file($l1, $l2 = '') {
    $dir = dirname($l1);
    if (!is_dir($dir)) {
        mkdirss($dir);
    }
    return @file_put_contents($l1, $l2);
}

/**
 * datetime类型处理
 * @param string $datetime
 */
function datetime_handle($datetime) {
    if (empty($datetime)) {
        return null;
    }
    return $datetime;
}

/**
 * 获取妈妈状态文字
 * @param int $state
 */
function get_mum_state_text($state) {
    $states = array('准备怀孕', '已怀孕', '已有宝宝');
    return $states[$state];
}

/**
 * 获取奖励日期
 * @param string $datetime
 */
function get_recommend_state($datetime) {
    if (empty($datetime)) {
        return '未奖励';
    }
    return $datetime;
}

/**
 * 会员类型
 * @param type $status
 * @return string
 */
function show_member_reg($reg_type) {
    switch ($reg_type) {
        case 0:
            return "后台添加会员";
        case 1:
            return "自主注册会员";
        case 2:
            return "协助注册会员";
    }
}

function show_level_time($time) {
    if ($time == "2015-01-01") {
        return "无限期";
    } else {
        return "有效期至：" . $time;
    }
}

/*
 * 获取问卷单选多选类型
 */

function get_type($reslist_id) {
    $type = M('OnlineResearchList')->where(array('id' => $reslist_id))->getField('check_type');
    if ($type == 2) {
        return "多选";
    } elseif ($type == 1) {
        return "单选";
    } else {
        return "未知";
    }
}

function getExam($id) {
    $info = M('OnlineResearchList')->where(array('id' => $id))->find();
    $str = "<b>" . $info['title'] . "</b><div>A:" . $info['answer_a'] . "</div><div>B:" . $info['answer_b'] . "</div>";
    if (!empty($info['answer_c'])) {
        $str = $str . "<div>C:" . $info['answer_c'] . "</div>";
    }
    if (!empty($info['answer_d'])) {
        $str = $str . "<div>D:" . $info['answer_d'] . "</div>";
    }
    if (!empty($info['answer_e'])) {
        $str = $str . "<div>E:" . $info['answer_e'] . "</div>";
    }
    if (!empty($info['answer_f'])) {
        $str = $str . "<div>F:" . $info['answer_f'] . "</div>";
    }
    if (!empty($info['answer_g'])) {
        $str = $str . "<div>G:" . $info['answer_c'] . "</div>";
    }

    return $str;
}

function getExamStr($reslist_id) {
    $info = M('OnlineResearchList')->where(array('id' => $reslist_id))->find();
    $str = $info['title'] . "\n" . $info['answer_a'] . "\n" . $info['answer_b'];
    if (!empty($info['answer_c'])) {
        $str = $str . "\n" . $info['answer_c'];
    }
    if (!empty($info['answer_d'])) {
        $str = $str . "\n" . $info['answer_d'];
    }
    if (!empty($info['answer_e'])) {
        $str = $str . "\n" . $info['answer_e'];
    }
    if (!empty($info['answer_f'])) {
        $str = $str . "\n" . $info['answer_f'];
    }
    if (!empty($info['answer_g'])) {
        $str = $str . "\n" . $info['answer_g'];
    }
    return $str;
}

function show_perfect($perfect) {
    switch ($perfect) {
        case 0:
            return "<span class='btn btn-xs btn-warning'>未完善</span>";
        case 1:
            return "<span class='btn btn-xs btn-success'>已完善</span>";
    }
}
