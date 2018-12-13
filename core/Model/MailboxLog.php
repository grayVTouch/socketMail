<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/20
 * Time: 17:53
 */

namespace Core\Model;


class MailboxLog
{
    public static function save($data = []){
//        var_dump(db()->table('mailbox_log')->insertGetId($data , true));
        return db()->table('mailbox_log')->insertGetId($data);
    }

    public static function get($mail_id = ''){
        return db()->table('mailbox_log')->where('mail_id' , $mail_id)->first();
    }

    // 增加错误次数
    public static function incr($mail_id = ''){
        return db()->table('mailbox_log')->where('mail_id' , $mail_id)->incr('count' , 1);
    }
}