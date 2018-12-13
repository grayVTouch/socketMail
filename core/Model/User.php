<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/12/13
 * Time: 14:47
 */

namespace Core\Model;


class User
{
    // 获取用户
    public static function get($token)
    {
        return db()->table('user')->where('token' , $token)->first();
    }
}