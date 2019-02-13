<?php
namespace Library\Util;

/*
 * Aes加密
 * 工作模式/填充方式 为 ECB/PKCS5Padding
 */

class Aes {

    /* 
     * 加密函数
     * 加密时直接传入需要加密的字符串，以及base64解码后的密钥
     */
    public static function encrypt($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = Aes::_pkcs5_pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $data;
    }

    /* 
     * 解密函数
     * 解密时直接传入需要解密的字符串，以及base64解码后的密钥
     */
    public static function decrypt($sStr, $sKey) {
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $sKey, base64_decode($sStr), MCRYPT_MODE_ECB);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s - 1]);
        return substr($decrypted, 0, -$padding);
    }

    private static function _pkcs5_pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    
}
