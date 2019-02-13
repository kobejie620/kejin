<?php

/** ==========================================
 * 
 *           --- 声明 ---
 * 
 * 此文件是平台实现的核心部分，请不要做调整及更改，
 * 任何人员如需对此文件修改，请事先与平台构建者沟通！
 * 
 * 擅自改动此文件可能促使整个平台崩溃，引发致命错误！
 * 
 * ----------------------------------------
 * @author zoujingli <zoujingli@qq.com>
 * @date 2015/04/14 14:16
 * =========================================
 */

namespace Library\Controller;

use Library\Util\Page;
use Think\Controller as ThinkController;

/**
 * 公共控制器主类
 * 包涵常用操作方法及自动实现机制
 * 
 * @author zoujingli <zoujingli@qq.com>
 * @date 2015/04/14 14:16
 */
class Controller extends ThinkController {

    /**
     * 模块标题
     * @var type 
     */
    public $ptitle = '';

    /**
     * 设置列表开启分页
     * @var type 
     */
    protected $_page_on = true;

    /**
     * 绑定控制器的模型名称
     * @default 默认与控制名相同
     * @var type
     */
    protected $_bind_model = CONTROLLER_NAME;

    /**
     * 绑定控制器数据列表过滤条件
     * @var type 
     */
    protected $_bind_map = array();

    /**
     * 首页数据列表显示
     * 
     * @callback _filter Model Map
     * @param Model $model 数据模型对象
     * @param array $map   数据模型查找规则
     * @param string $tpl 显示的模板名
     * @display
     */
    public function index($model = null, $map = array(), $tpl = null) {
        is_null($model) && $model = D($this->_bind_model);
        empty($map) && $map = $this->_bind_map;
        $action = I('get.action', false, 'trim');
        if ($action === 'saveOrder') {
            die($this->_saveOrder($model));
        }
        $this->_search($model, $map);
        if (
                false !== $this->_callback('filter', $model, $map, false) &&
                false !== $this->_callback('filter', $model, $map)
        ) {
            $this->assign('ptitle', $this->ptitle);
            $this->_list($model, $map);
            $this->display($tpl);
        }
    }

    /**
     * 保存排序数据
     * 
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/09/16 11:55:26
     * 
     * @param \Think\Model $model 需要操作的模型
     * @param string $field 排序操作的字段名
     */
    protected function _saveOrder($model, $field = 'sort') {
        $fields = $model->getDbFields();
        if (!in_array($field, $fields)) {
            $this->progress('该模块不有使用排序功能', 0);
        }
        $data = I('post.', array());
        $count = count($data);
        if ($count < 1) {
            $this->progress('没有需要需要排序的数据', 0);
        }
        $i = 0;
        foreach ($data as $key => $vo) {
            $arr = explode('_', $key);
            if (count($arr) !== 3) {
                continue;
            }
            if (false === $model->save(array($model->getPk() => $arr[2], $field => $vo))) {
                $this->progress('排序操作失败，请稍后再试！', 0);
            }
            $i++;
            $this->progress("正在处理排序数据({$i}/{$count})");
        }
        $this->progress('排序操作完成，刷新数据中...', 1);
    }

    /**
     * 搜索条件处理
     * 
     * @param Model $model 数据对象模型
     * @param array $map  数据模型查找规则
     * @return array  处理之后的数据模型查找规则
     */
    protected function _search(&$model, &$map) {
        // 去除空格
        foreach ($_REQUEST as $k => $value) {
            if (is_string($value)) {
                $_REQUEST[$k] = trim($value);
            }
            if ($value === '') {
                unset($_REQUEST[$k]);
            }
        }

        //判定查询方式
        function getQueryValue($val) {
            if (is_numeric($val)) {
                return array('eq', $val);
            } elseif (is_string($val) && !empty($val)) {
                return array('like', "%{$val}%");
            } elseif (is_null($val)) {
                return array('exp', 'is null');
            } else {
                return $val;
            }
        }

        // 自定义 _fields 指定要查找的字段
        $dbFields = $model->getDbFields();
        if (!empty($_REQUEST['_fields'])) {
            $dbFields = array_intersect($dbFields, explode(',', $_REQUEST['_fields']));
        } else {
            foreach ($_REQUEST as $key => $vo) {
                $key = str_replace('-', '.', $key);
                if (in_array($key, $dbFields) || strpos($key, '.') !== false) {
                    $map[$key] = getQueryValue($vo);
                }
            }
        }
        $_keys = I('request._keys', '', 'trim');
        if (!empty($_keys)) {
            $map[join('|', $dbFields)] = array('EXP', "LIKE BINARY '%{$_keys}%'");
        }
        return $map;
    }

    /**
     * 列表显示处理
     * 
     * @callback _list_filter Model Map
     * @callback _data_filter List Model
     * @param Model $model 数据模型对象
     * @param array $map  数据模型查找规则
     * @return array 本页显示的数据
     */
    protected function _list($model, $map) {
        // 设置排序字段
        $_order = I('get._order', val('_order'), 'trim');
        $dbFields = $model->getDbFields();
        if (stripos($_order, ':force') === false && !in_array($_order, $dbFields) && stripos($_order, '.') === false) {
            $_order = in_array('sort', $dbFields) ? 'sort' : $model->getPk();
        }
        $_order = str_replace(':force', '', $_order);
        $this->assign('_order', $_order);

        // 设置排序顺序
        $asc = I('get._sort', (val('_sort') ? 0 : 1), 'trim');
        $_sort = $asc ? 'asc' : 'desc';
        $this->assign('_sort', ($_sort == 'desc') ? 1 : 0);
        $this->assign('_sort_type', ($_sort == 'desc') ? '倒序排列' : '升序排列');

        $count = 0;
        if ($this->_page_on) {
            // 统计数据总记录
            $this->_callback('list_filter', $model, $map, false);
            $this->_callback('list_filter', $model, $map);
            if (stripos($model->buildSql(), 'GROUP BY') !== false) {
                $this->_callback('list_filter', $model, $map, false);
                $this->_callback('list_filter', $model, $map);
                $count = count($model->field('1')->where($map)->select());
            } else {
                $this->_callback('list_filter', $model, $map, false);
                $this->_callback('list_filter', $model, $map);
                $count = $model->where($map)->count('1');
            }
        }

        $voList = array();
        if ($count > 0 || !$this->_page_on) {
            if ($this->_page_on) {
                // 设置分页信息
                $prow = I('get.prow', (cookie('prow') > 0 ? cookie('prow') : 20), 'intval');
                cookie('prow', $prow, array('expire' => 86400));
                $page = new Page($count, intval($prow));
                if (!empty($this->_page_style)) {
                    foreach ($this->_page_style as $key => $value) {
                        $page->setConfig($key, $value);
                    }
                }
                $this->assign("page", $page->show());
                //分页查询数据
                $this->_callback('list_filter', $model, $map, false);
                $this->_callback('list_filter', $model, $map);
                $voList = $model->where($map)->order("`{$_order}`" . ' ' . $_sort)->page($page->nowPage, $page->listRows)->select();
            } else {
                //查询所有数据
                $this->_callback('list_filter', $model, $map);
                $voList = $model->where($map)->order("`{$_order}`" . ' ' . $_sort)->select();
            }
            /* 如果在Action中有定义数据二次过滤方法 */
            if (
                    false !== $this->_callback('data_filter', $voList, $model, false) &&
                    false !== $this->_callback('data_filter', $voList, $model)
            ) {
                //解析字段中的点
                $this->_parseKeyDot($voList);
                //模板赋值
                $this->assign('list', $voList);
            }
        }
        return $voList;
    }

    /**
     * 解析组成Key中的点，生成数组树结构
     * 
     * @param type &$list 需要处理的二维列表数组
     * @access protected
     * @author zoujingli <zoujingli@qq.com>
     * @date 2014/10/24 14:33:50
     */
    protected function _parseKeyDot(&$list) {

        /**
         * 【递归】生成多维数组树
         * @param array $key_arr 数组的键
         * @param type $value 数组的值
         * @return type
         */
        function parse_key_dot(array $key_arr, $value = null) {
            $key = array_shift($key_arr);
            if (empty($key_arr)) {
                return array($key => $value);
            }
            return array($key => parse_key_dot($key_arr, $value));
        }

        //遍历数组列表
        foreach ($list as &$row) {
            // 取得数组列表单元所有的Key，并单独解析处理
            foreach (array_keys($row) as $key) {
                if (is_string($key) && stripos($key, '.')) {
                    $key_arr = explode('.', $key);
                    $row = array_merge_recursive($row, parse_key_dot($key_arr, $row[$key]));
                }
            }
        }
    }

    /**
     * 添加数据
     * @param type $model
     * @param type $map
     * @param type $tpl
     */
    public function add($model = null, $map = array(), $tpl = 'form') {
        is_null($model) && $model = D($this->_bind_model);
        if (!IS_POST) {
            $vo = I('request.');
            empty($this->ptitle) && $this->ptitle = '添加记录';
            if (
                    false !== $this->_callback('form_filter', $model, $vo, false) &&
                    false !== $this->_callback('filter', $model, $vo)
            ) {
                $this->assign('ptitle', $this->ptitle);
                $this->assign('vo', $vo);
                $this->display($tpl);
            }
        } elseif (
                false !== $this->_callback('form_filter', $model, $_POST, false) &&
                false !== $this->_callback('filter', $model, $_POST)
        ) {

            $this->__doing_save($model, $map, $_POST);
        }
    }

    /**
     * 编辑数据
     * @param type $model
     * @param type $map
     * @param type $tpl
     */
    public function edit($model = null, $map = array(), $tpl = 'form') {
        is_null($model) && $model = D($this->_bind_model);
        if (!IS_POST) {
            $id = I('get.id');
            empty($id) || $map[$model->getPk()] = I('get.id');
            $vo = $model->where($map)->find();
            !empty($id) && empty($vo) && $this->error('抱歉，访问的数据不存在！');
            empty($this->ptitle) && $this->ptitle = '编辑';
            if (
                    false !== $this->_callback('form_filter', $model, $vo, false) &&
                    false !== $this->_callback('filter', $model, $vo)
            ) {
                $this->assign('ptitle', $this->ptitle);
                $this->assign('vo', $vo);
                $this->display($tpl);
            }
        } elseif (
                false !== $this->_callback('form_filter', $model, $_POST, false) &&
                false !== $this->_callback('filter', $model, $_POST)
        ) {
            $this->__doing_save($model, $map, $_POST);
        }
    }

    /**
     * 增加/修改记录时保存操作
     * @param type $model
     * @param type $map
     * @param type $data
     */
    private function __doing_save($model, $map, $data = null) {
        $result = $this->_save($data, $model, $map);
        if ($result['status']) {
            if (
                    false !== $this->_callback('form_success', $model, $result['data'], false) &&
                    false !== $this->_callback('success', $model, $result['data'])
            ) {
                $result['url'] = I('post.referer', null, 'U');
                IS_AJAX ? $this->ajaxReturn($result) : $this->success('数据保存成功！', $result['url']);
            }
        } else {
            if (
                    false !== $this->_callback('form_error', $model, $result['data'], false) &&
                    false !== $this->_callback('error', $model, $result['data'])
            ) {
                IS_AJAX ? $this->ajaxReturn($result) : (empty($result['info']) ? '数据保存失败，请稍候再试' : $result['info']);
            }
        }
    }

    /**
     * 通用 数据禁用操作
     * 
     * @param Model $model 数据对象模型
     * @param array $map 数据模型查找规则
     */
    public function forbid($model = null, $map = array()) {
        if (IS_POST && IS_AJAX) {
            is_null($model) && $model = D($this->_bind_model);
            $ids = explode(',', I('post.id'));
            $status = I('post.status', 1, 'intval');
            if (false !== $this->_callback('filter', $model, $ids)) {
                empty($ids) && $this->error('数据状态操作失败，请稍候再试！CODE[10001]');
                $map[$model->getPk()] = array('in', $ids);
                if (!empty($map) && false !== $model->where($map)->save(array('status' => "{$status}"))) {
                    $data = array('ids' => $ids, 'status' => $status);
                    (false !== $this->_callback('success', $model, $data)) && $this->success('数据状态操作成功！');
                } else {
                    (false !== $this->_callback('error', $model, $data)) && $this->error('数据状态操作失败，请稍候再试！CODE[10002]');
                }
            }
        } else {
            $this->error('访问异常，可能是你的人品太差了 ~_~');
        }
    }

    /**
     * 启用数据
     * @param type $model
     * @param type $map
     */
    public function resume($model = null, $map = array()) {
        if (IS_POST && IS_AJAX) {
            is_null($model) && $model = D($this->_bind_model);
            $ids = explode(',', I('post.id'));
            $status = I('post.status', 2, 'intval');
            if (false !== $this->_callback('filter', $model, $ids)) {
                empty($ids) && $this->error('数据状态操作失败，请稍候再试！CODE[10001]');
                $map[$model->getPk()] = array('in', $ids);
                if (!empty($map) && false !== $model->where($map)->save(array('status' => "{$status}"))) {
                    $data = array('ids' => $ids, 'status' => $status);
                    (false !== $this->_callback('success', $model, $data)) && $this->success('数据状态操作成功！');
                } else {
                    (false !== $this->_callback('error', $model, $data)) && $this->error('数据状态操作失败，请稍候再试！CODE[10002]');
                }
            }
        } else {
            $this->error('访问异常，可能是你的人品太差了 ~_~');
        }
    }

    /**
     * 默认删除操作
     * 
     * @param Model $model 数据对象模型
     * @param array $map 数据模型查找规则
     */
    public function del($model = null, $map = array()) {
        if (IS_POST && IS_AJAX) {
            is_null($model) && $model = D($this->_bind_model);
            $ids = explode(',', I('post.id'));
            if (false !== $this->_callback('filter', $model, $ids)) {
                empty($ids) && $this->error('删除失败，请稍候再试！CODE[10001]');
                $map[$model->getPk()] = array('in', $ids);
                if (!empty($map) && $model->where($map)->delete()) {
                    (false !== $this->_callback('success', $model, $ids)) && $this->success('删除成功！');
                } else {
                    (false !== $this->_callback('error', $model, $ids)) && $this->success('删除失败，请稍候再试！CODE[100002]');
                }
            }
        } else {
            $this->error('访问异常，可能是你的人品太差了 ~_~');
        }
    }

    /**
     * 保存数据（多条记录）
     * 
     * @param array $data 需要保存的数据
     * @param type $model 数据模型对象
     * @param type $startTrans 是否使用事务
     * @trans start 
     * @return result
     */
    protected function _saveAll($data, $model = null, $startTrans = false) {
        is_null($model) && $model = D($this->_bind_model);
        $result = array();
        $status = 1;
        $startTrans && $model->startTrans();
        foreach ($data as $key => $row) {
            $result[$key] = $this->_save($row, $model);
            if (empty($result[$key]['status'])) {
                $status = 0;
                if ($startTrans) {
                    break;
                }
            }
        }
        if ($startTrans) {
            $status ? $model->commit() : $model->rollback();
        }
        return array(
            'status' => $status,
            'info' => $result[$key]['info'],
            'result' => $result,
            'data' => $data
        );
    }

    /**
     * 数据保存（单条记录）
     * 
     * @param array $data 需要保存的数据
     * @param Model $model 数据模型对象
     * @param array $map 模式条件
     * @return array 执行后的结果 [status]=1,保存成功 [status]=0,保存失败
     */
    protected function _save($data = null, $model = null, $map = array()) {
        is_null($model) && $model = D($this->_bind_model);
        is_null($data) && $data = $_POST;
        $_data = $model->create($data);
        if (empty($_data)) {
            return array('status' => 0, 'info' => '数据创建失败 ' . $model->getError());
        }
        /* 检测数据是否已经存在 允许使用 where */
        if ($this->_isExistForDb($model, $_data, $map)) {
            if ($model->create($data) && false !== $model->where($map)->save()) {
                return array('status' => 1, 'info' => '数据保存成功', 'data' => $_data);
            }
        } else {
            if ($model->create($data)) {
                $id = $model->add();
                if (!empty($id)) {
                    $_data['id'] = $_data[$model->getPk()] = $id;
                    return array('status' => 1, 'info' => '数据保存成功', 'data' => $_data);
                }
            }
        }
        $msg = $model->getError();
        return array('status' => 0, 'info' => empty($msg) ? '数据保存失败，请稍候再试！' : $msg);
    }

    /**
     * 检查数据是否已经存在
     * 
     * @param \Think\Model $model 数据查找模型对象
     * @param array $data 需要添加的数据
     * @param array $map 核外的匹配条件
     * @return bool
     */
    protected function _isExistForDb(&$model = null, &$data = null, &$map = array()) {
        is_null($model) && $model = D($this->_bind_model);
        is_null($data) && $data = $_POST;
        $pk = $model->getPk();
        isset($data[$pk]) && $map[$pk] = $data[$pk];
        if (empty($map)) {
            return false;
        }
        return !!$model->where($map)->count();
    }

    /**
     * 对象方法回调公共方法
     * 
     * @param string $suffix 回调的方法后缀
     * @param string $param1 引用参数1
     * @param string $param2 引用参数2
     * @param string $prefix 回调方法前缀
     * @return type 方法执行的结果，未调用到方法 返回 Null
     */
    private function _callback($suffix, &$param1 = array(), &$param2 = array(), $prefix = ACTION_NAME) {
        $funcName = empty($prefix) ? "_{$suffix}" : "_{$prefix}_{$suffix}";
        if (method_exists($this, $funcName)) {
            return $this->$funcName($param1, $param2);
        }
        return null;
    }

    /**
     * 输出json数据 调整前端JS处理进度
     * @param type $info 提交消息
     * @param type $status 显示状态 -1 进行中， 0 发生错误， 1 完成了
     * @param type $url 跳转地址
     */
    protected function progress($info, $status = -1, $url = null) {
        if (val('_progress') !== true) {
            set_time_limit(0);
            C('SHOW_PAGE_TRACE', false);
            C('SHOW_ERROR_MSG', false);
            val('_progress', true);
        }
        $data = array('status' => $status, 'info' => $info, 'url' => $url);
        $progressKey = I('get.progress', 'progress', 'trim');
        ob_end_clean();
        /* 输出256字节空格（在ie浏览器下要接受大于256个字节才会输出显示） */
        echo str_pad(' ', 256);
        echo '<script>window.top.$("body").trigger("' . $progressKey . '",' . json_encode($data) . ');</script>';
        flush();
        ob_flush();
        if ($status !== -1) {
            exit;
        }
    }

}
