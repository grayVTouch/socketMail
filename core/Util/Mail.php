<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/16
 * Time: 9:54
 *
 * 邮件处理
 *
 * 邮件接收过程，费时
 * 邮件过滤过程，秒速
 * 邮件入库过程，费时
 */

namespace Core\Util;

use PhpImap\Mailbox;
use Core\Mail\MailWrapper;
use Core\Model\Mailbox as MMailbox;

class Mail
{
    // 连接类型
    protected $_connectAddr = '';
    // 邮箱账号
    protected $_username = '';
    // 密码（授权码，一般是授权码）
    protected $_password = '';
    // 附件目录
    protected $_attachmentDir = '';
    // 编码
    protected $_encoding = '';
    // 邮箱客户端
    protected $_client = null;
    // 邮箱
    protected $_mailbox = '';
    // 协议
    protected $_protocol = '';

    function __construct($mailbox_id , $protocol , $username , $password , $attachment_dir = '' , $encoding = 'UTF-8'){
        // 获取邮箱
        $mailbox = MMailbox::get($mailbox_id);
        $this->_mailbox      = $mailbox->name;
        $this->_protocol     = $protocol;
        $this->_connectAddr  = get_mailbox($mailbox->id , $this->_mailbox , $protocol);
        $this->_username    = $username;
        $this->_password    = $password;
        $this->_attachmentDir = $attachment_dir;
        $this->_encoding     = $encoding;
        // 客户端
        $this->_client = new Mailbox($this->_connectAddr , $this->_username , $this->_password , $this->_attachmentDir , $this->_encoding);
    }

    // 获取邮件 id
    public function ids(){
        return array_reverse($this->_client->searchMailbox('ALL'));
    }

    // 获取原生邮件
    public function mailRaw($mail_id){
        return $this->_client->getMail($mail_id);
    }

    // 获取邮件
    public function mail($mail_id){
        $mail = $this->_client->getMail($mail_id);
        $mail = $this->single($mail);
        return $mail;
    }

    // 获取所有邮件
    public function mails($ids = []){
        $res = [];
        foreach ($ids as $v)
        {
            $res[] = $this->mail($v);
        }
        return $res;
    }

    // 单条处理
    public function single($mail){
        // 邮件包装器
        $mail_wrapper = new MailWrapper();
        $mail_wrapper->id   = $mail->id;
        $mail_wrapper->from = $mail->fromAddress;
        $mail_wrapper->to   = $this->_username;
        $mail_wrapper->fromName = $mail->fromName;
        $mail_wrapper->toName   = $mail->toString;
        $mail_wrapper->cc   = $mail->cc;
        $mail_wrapper->bcc  = $mail->bcc;
        $mail_wrapper->html = $mail->textHtml;
        $mail_wrapper->text = $mail->textPlain;
        $mail_wrapper->mailId = $mail->messageId;
        // $mail_wrapper->bankName = get_correct_bank($mail_wrapper->from);
        $mail_wrapper->bankCode = get_correct_bank_code($mail_wrapper->from);
        return $mail_wrapper;
    }

    // 多条处理
    public function multiple($mail){
        $res = [];
        foreach ($mail as $v)
        {
            $res[] = self::single($v);
        }
        return $res;
    }

    // 判断是否是要抓取的邮件
    public function contain($from = '' , array $range = []){
        $banks = empty($range) ? array_values(config('app.from_bank')) : $range;
        foreach ($banks as $v)
        {
            if ($from == $v) {
                return true;
            }
        }
        return false;
    }

    // 过滤单条邮件
    // @param array $mails 邮件
    // @param string|array $from 发件人
    // 如果没有提供 $from 参数，那么就仅获取在配置文件中设置的银行卡列表
    public function filter($mail , $from = []){
        return $this->contain($mail->from , $from) ? $mail : null;
    }

    // 关闭连接
    public function close(){

    }
}



