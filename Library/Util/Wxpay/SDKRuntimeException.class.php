<?php
namespace Library\Util\Wxpay;
/**
 * Description of SDKRuntimeException
 *
 * @author tanglinjun
 * @date 2015-02-10
 */
class SDKRuntimeException extends \Think\Exception {

    public function errorMessage() {
        return $this->getMessage();
    }

}
