<?php

namespace Library\Util;

/**
 * 邮件发送库文件 Mail
 * 
 * @version  3.0
 * @author zoujingli <zoujingli@qq.com>
 * @dateTime 2015/04/01 15:23
 * 
 * <code>
 * $mail = new \Library\Util\Mail();
 * $mail->setAddress('anyon@139.com')                   // 发送地址
 *      ->setCC('94620616@qq.com;zoujingli@qq.com')     //抄送地址
 * 	->setBCC('anyon@139.com')                       //密送地址
 * 	->setSubject('测试标题')                         //邮件标题
 * 	->setContent('测试内容')                         //邮件内容
 * 	->setFromName('发送人333')                       //发送人名称
 * 	->setAttach('E:/wamp/unins000.exe')             //附件
 * 	->setReply('zoujingli@qq.com')                  //回复地址
 * 	->send();                                       //执行发送
 * </code>
 */
class Mail {

    /**
     * 常用消息
     * @var array 
     */
    private static $error = array();

    /**
     * 邮件操作对象
     * @var PHPMailer 
     */
    private static $mailer = null;

    /**
     * 构造方法 初始化Mailer配置
     * 
     * @param type $config
     */
    public function __construct() {

        vendor('PHPMailer.class#phpmailer');
        vendor('PHPMailer.class#smtp');


        //# 初始化邮件基础模块
        self::$mailer = new \PHPMailer();
        self::$mailer->IsSMTP();
        self::$mailer->SMTPAuth = !!get_sysconfig('mail_smtp_auth');
        self::$mailer->CharSet = 'UTF-8'; //邮件编码
        self::$mailer->Host = get_sysconfig('mail_smtp');
        self::$mailer->From = get_sysconfig('mail_from');
        self::$mailer->FromName = get_sysconfig('mail_from_name');
        self::$mailer->Username = get_sysconfig('mail_username');
        self::$mailer->Password = get_sysconfig('mail_password');
        self::$mailer->SMTPSecure = get_sysconfig('mail_secure');
        self::$mailer->Port = get_sysconfig('mail_port');
        self::$mailer->IsHTML();
        $reply = get_sysconfig('mail_reply');
        if (!empty($reply)) {
            $this->setReply($reply);
        }
    }

    /**
     * 设置收信人地址
     * @param string|array|null $address 如果设置值为null则是清空原来的值,传字符串请以分号分割
     * @return \MailAction
     */
    public function setAddress($address = null) {
        if (null === $address) {
            self::$mailer->clearAddresses();
        } else {
            $address = self::__parseParam($address, 'array');
            foreach ($address as $addr) {
                self::$mailer->AddAddress(trim($addr));
            }
        }
        return $this;
    }

    /**
     * 设置抄送人邮箱
     * @param string|array|null $cc 如果设置值为null则是清空原来的值
     * @return \MailAction
     */
    public function setCC($cc = null) {
        if (null === $cc) {
            self::$mailer->ClearCC();
        } else {
            foreach (self::__parseParam($cc, 'array') as $c) {
                self::$mailer->AddCC(trim($c));
            }
        }
        return $this;
    }

    /**
     * 设置密送人邮箱
     * @param string|array|null $bcc 如果设置值为null则是清空原来的值
     * @return \MailAction
     */
    public function setBCC($bcc = null) {
        if (null === $bcc) {
            self::$mailer->ClearBCC();
        } else {
            $bcc = self::__parseParam($bcc, 'array');
            foreach ($bcc as $b) {
                self::$mailer->AddBCC(trim($b));
            }
        }
        return $this;
    }

    /**
     * 设置发信人的名称
     * @param string $fromName
     * @return \MailAction
     */
    public function setFromName($fromName) {
        self::$mailer->FromName = $fromName;
        return $this;
    }

    /**
     * 设置邮件的内容
     * @param string $content
     * @return \MailAction
     */
    public function setContent($content) {
        self::$mailer->Body = $content;
        return $this;
    }

    /**
     * 设置邮件的标题
     * @param string $subject
     * @return \MailAction
     */
    public function setSubject($subject) {
        self::$mailer->Subject = $subject;
        return $this;
    }

    /**
     * 设置邮件附件
     * @param array|string|null $attach  如果设置值为null则是清空原来的值
     * @return \MailAction
     */
    public function setAttach($attach = null) {
        if (null === $attach) {
            self::$mailer->ClearAttachments();
        } else {
            $attach = self::__parseParam($attach, 'array');
            foreach ($attach as $a) {
                if (file_exists($a)) {
                    self::$mailer->AddAttachment(trim($a));
                } else {
                    self::$error[] = array('-1', '附件没有找到！', $a);
                }
            }
        }
        return $this;
    }

    /**
     * 设置回信接收地址 
     * @param array|string|null $reply  如果设置值为null则是清空原来的值
     * @return \MailAction
     */
    public function setReply($reply = null) {
        if (null === $reply) {
            self::$mailer->ClearReplyTos();
        } else {
            $reply = self::__parseParam($reply, 'array');
            foreach ($reply as $rpy) {
                self::$mailer->AddReplyTo(trim($rpy));
            }
        }
        return $this;
    }

    /**
     * 解释参数  数组与字串之间转换
     * @param array|string $param	需要解释的参数 [array|string]
     * @param string $type 返回的数据类型 [array|string]
     * @param string $delimiter 分割符
     * @return array|string
     */
    private static function __parseParam($param, $type = 'string', $delimiter = ';') {
        if (is_string($param)) {
            $param = str_replace(',', ';', $param);
        }
        if (is_array($param) && 'string' === $type) {
            return join($delimiter, $param);
        } elseif (is_string($param) && 'array' === $type) {
            return explode($delimiter, $param);
        } else {
            return $param;
        }
    }

    /**
     * 执行邮件发送
     * @return boolean
     */
    public function send() {
        return !!self::$mailer->Send();
    }

    public function getMsg() {
        return self::$mailer->ErrorInfo;
    }

}
