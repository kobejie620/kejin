<?php

namespace Library\Util;

/**
 * 快递模块模拟查询类
 * 
 * @author zoujingli <zoujingli@qq.com>
 * 
 */
class Express {

    /**
     * 地址获取编码类型的地址
     * @var type 
     */
    protected $type_url = 'http://www.kuaidi100.com/autonumber/autoComNum?text={code}';

    /**
     * 快递记录查询的地址
     * @var type 
     */
    protected $query_url = 'http://www.kuaidi100.com/query?type={type}&postid={code}&id=1';

    /**
     * 获取查询出来的结果集合
     * @var type 
     */
    protected $result = array();

    /**
     * 快递码
     * @var type 
     */
    protected $code = '';

    /**
     * 查询快递码
     * @param type $code 快递码
     * @param type $type 快递类型代码
     * @return type
     */
    public function query($code, $type = null) {
        $this->code = $code;
        if (is_null($type)) {
            $type = $this->_getTypeCode();
        } else {
            $type = array('auto' => $type);
        }
        if (isset($type['auto'])) {
            foreach ($type['auto'] as $type) {
                $this->result[$type['comCode']] = $this->_getRecode($type['comCode']);
            }
        }
        return $this->result;
    }

    /**
     * 读取指定类型的快递记录
     * @param type $type
     * @return type
     */
    protected function _getRecode($type) {
        $snoopy = $this->getInstance();
        $snoopy->fetch(str_replace(array('{code}', '{type}'), array($this->code, $type), $this->query_url));
        return json_decode($snoopy->results, true);
    }

    /**
     * 自动识别快递类型
     * @return type
     */
    protected function _getTypeCode() {
        $snoopy = $this->getInstance();
        $snoopy->fetch(str_replace('{code}', $this->code, $this->type_url));
        return json_decode($snoopy->results, true);
    }

    /**
     * 获取URL操作对象
     * @staticvar type $snoopy
     * @return \Library\Util\Snoopy
     */
    protected function getInstance() {
        static $snoopy = null;
        if (is_null($snoopy)) {
            $snoopy = new Snoopy();
            $snoopy->referer = 'http://www.kuaidi100.com';
        }
        return $snoopy;
    }

}
