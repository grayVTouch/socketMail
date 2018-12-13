<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/12/6
 * Time: 17:52
 */

namespace Core\Model;


class MailboxConfig
{
    // 通过 id 获取
    public static function get($id){
        db()->table('mailbox_config')->where('id' , $id)->first();
    }

    // 通过 id 获取
    public static function find($mailbox_id , $protocol){
        return db()->table('mailbox_config')->where([
            ['mailbox_id' , '=' , $mailbox_id] ,
            ['protocol'   , '=' , $protocol] ,
        ])->first();
    }
}