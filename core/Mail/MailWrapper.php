<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/19
 * Time: 14:30
 *
 * 邮件包装器
 */

namespace Core\Mail;

class MailWrapper
{
    // 邮件 id，每次获取都不同
    public $id = '';
    // 发件人邮箱地址
    public $from = '';
    // 收件人邮箱地址
    public $to = '';
    // 发件人名称
    public $fromName = '';
    // 接收人名称
    public $toName = '';
    // 主题
    public $subject = '';
    // 抄送
    public $cc = '';
    // 按抄送
    public $bcc = '';
    // 邮件id，唯一
    public $mailId = '';
    // 发卡行
    public $bank = '';
    // 邮件内容：text
    public $text = '';
    // 邮件内容：html
    public $html = '';
    // 发卡行名称
    // public $bankName = '';
    // 发卡行代码
    public $bankCode = '';
}