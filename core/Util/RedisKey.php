<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/12/13
 * Time: 16:59
 *
 * redis 中维护的键名
 */

namespace Core\Util;


/**
 * 相关映射关系
 * user_id -> user_info
 * conn_id -> user_id
 * user_id -> task
 */

class RedisKey
{
// redis 中保存的用户 key
    protected $key = [
        // 用户信息 key
        'user' => 'user_%d' ,
        'conn' => 'conn_%d' ,
        'task' => 'task_%d' ,
    ];

    // user_key
    public function uk($user_id)
    {
        return sprintf($this->key['user'] , $user_id);
    }

    // conn key
    public function ck($conn_id)
    {
        return sprintf($this->key['conn'] , $conn_id);
    }

    // task key
    public function tk($user_id)
    {
        return sprintf($this->key['task'] , $user_id);
    }
}