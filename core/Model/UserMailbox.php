<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/20
 * Time: 17:10
 */

namespace Core\Model;

class UserMailbox
{
    public static function get($id = 0){
        return db()->table('user_mailbox')->where('id' , $id)->first();
    }
}