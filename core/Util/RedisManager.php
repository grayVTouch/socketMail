<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/12/12
 * Time: 14:41
 *
 * redis 中保存的用户信息管理
 */

namespace Core\Util;

use Core\Lib\Redis;

class RedisManager extends RedisKey
{
    protected $redis = null;

    function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    // 新增客户端连接
    public function online($conn_id , $user)
    {
        $_user = $this->get($this->uk($user->uid));
        if ($_user === false) {
            // 创建
            $_user = [
                // 用户信息
                'user' => $user ,
                // 客户端连接
                'conn' => [
                    $conn_id
                ]
            ];
        } else {
            // 新增新数据
            $_user->user    = $user;
            $_user->conn[]  = $conn_id;
        }
        // 保存用户 和 任务 的映射关系
        $this->taskMapping($user->uid);
        // 保存连接 和 用户id 的映射关系
        $this->set($this->ck($conn_id) , $user->uid);
        // 保存用户信息
        $this->set($this->uk($user->uid) , $_user);
    }

    // redis get
    public function get($key)
    {
        $res = $this->redis->string($key);
        if ($res === false) {
            return false;
        }
        return json_decode($res);
    }

    // redis set
    public function set($key , $value = '')
    {
        return $this->redis->string($key , json_encode($value));
    }

    // redis del
    public function del($key)
    {
        return $this->redis->del($key);
    }

    // 用户下线
    public function offline($conn_id)
    {
        $user_id = $this->userId($conn_id);
        if ($user_id === false) {
            return ;
        }
        // 删除掉映射关系
        $this->del($this->ck($conn_id));
        $user = $this->get($this->uk($user_id));
        if ($user === false) {
            // 没有保存用户，退出
            return ;
        }
        $conn = [];
        // 已经保存用户信息，删除掉下线的连接
        foreach ($user->conn as $k => $v)
        {
            if ($v != $conn_id) {
                // 仅保存在线客户端
                $conn[] = $v;
            }
        }
        $user->conn = $conn;
        if (empty($user->conn)) {
            // 如果所有客户端都已经下线，那么删除掉 redis 中保存的信息
            $this->del($this->uk($user_id));
            return ;
        }
        $this->set($this->uk($user_id) , $user);
    }

    // 任务映射
    public function taskMapping($user_id)
    {
        if ($this->get($this->tk($user_id)) === false) {
            // 不存在，创建
            $_task = [
                'status' => 'none' ,
                'data' => [
                    'total'             => 0 ,
                    'filter_by_from'    => 0 ,
                    'filter_by_repeat'  => 0 ,
                    'success_for_parse' => 0 ,
                    'fail_for_parse'    => 0 ,
                ]
            ];
            $this->set($this->tk($user_id) , $_task);
        }
    }

    // 设置任务状态
    public function taskStatus($user_id , $status = '' , $data = [])
    {
        $task = $this->get($this->tk($user_id));
        $task->status   = $status;
        $task->data     = $data;
        // 设置新数据
        $this->set($this->tk($user_id) , $task);
    }

    // 检查连接是否存在
    public function existConn($conn_id)
    {
        $user_id = $this->userId($conn_id);
        if ($user_id === false) {
            return false;
        }
        return true;
    }

    // 获取 user_id
    public function userId($conn_id)
    {
        $user_id = $this->get($this->ck($conn_id));
        if ($user_id === false) {
            return false;
        }
        return intval($user_id);
    }

    // 删除 key
    public function clearKey()
    {

    }
}