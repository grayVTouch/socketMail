<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/12/6
 * Time: 18:03
 */

namespace Core\Model;


class Mailbox
{
    public static function all(){
        return db()->table('mailbox')->select()->get();
    }

    // 获取邮箱
    public static function get($id){
        return db()->table('mailbox')->where('id' , $id)->first();
    }
}