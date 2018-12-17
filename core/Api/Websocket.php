<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/28
 * Time: 9:35
 *
 * 接口调度
 */

namespace Core\Api;

use Exception;
use Core\Model\User;
use Core\Connection\Websocket as WebsocketConnection;
use Core\Util\RedisManager;

class Websocket extends Response
{
    // 失败
    public const ERROR = 'error';
    // 成功
    public const SUCCESS = 'success';

    /**
     * @see \Core\Connection\Websocket
     */
    protected $socket = null;

    /**
     * @see \Core\Util\Redis
     */
    protected $redis = null;

    function __construct(WebsocketConnection $socket , RedisManager $redis){
        $this->socket = $socket;
        $this->redis = $redis;
    }
    
    // 要排除验证的接口列表
    protected $exclude = ['login' , 'send'];

    // 在方法执行之前，必须验证登录状态
    public function verify($fd , $intent = '' , $data = null){
        if (in_array($intent , $this->exclude)) {
            // 如果是排除验证的 api ，统统通过
            return true;
        }
        $user_id = $this->redis->userId($fd);
        $info = $this->redis->get($this->redis->uk($user_id));
        if ($info === false) {
            return false;
        }
        $_user = $info->user;
        // 有效时长
        $duration   = config('time.duration');
        $_user->token_time = intval($_user->token_time);
        if ($_user->token_time + $duration < time()) {
            // 已经过期
            return false;
        }
        return true;
    }

    // 服务器收：登录
    public function login($fd , $intent , $data = null){
        if ($this->redis->existConn($fd)) {
            // 表示用户在连接内，重复发起注册请求
            // 我表示开发者可以拉出去枪毙了
            $this->send($fd , self::ERROR , $intent , '已经登录');
            return ;
        }
        if (!isset($data->token) || empty($data->token)) {
            $this->socket->close($fd , 1000 , "必须提供 token");
            return ;
        }
        $user = User::get($data->token);
        if (is_null($user)) {
            $this->socket->close($fd , 1000 , "未找到当前 token: {$data->token} 对应用户");
            return ;
        }
        // 用户上线
        $this->redis->online($fd , $user);
        // 如果该用户之前有已经完成的任务尚未接收到通知，则通知成功信息
        $task = $this->redis->get($this->redis->tk($user->uid));
        if ($task->status == 'completed') {
            $this->completed($user->uid , 'completed' , '' , $task->data);
        }
        // 发送用户登录成功的消息
        $this->send($fd , self::SUCCESS , $intent , '登录成功');
    }

    // 服务器收：解析
    public function parse($fd , $intent , $data = null){
        // todo 检查当前调用接口用户是否已经存在一个运行中的任务
        // todo 如果存在，则不允许再次创建任务
        $user_id = $this->redis->userId($fd);
        $task = $this->redis->get($this->redis->tk($user_id));
        if ($task->status === 'running') {
            $this->send($fd , self::ERROR , $intent , '您尚有一个任务正在运行中，请等待该任务完成');
            return ;
        }
        // 添加异步任务
        $this->socket->task(compact('user_id' , 'intent' , 'data'));
        // 设置任务为更新状态
        $this->redis->taskStatus($user_id , 'running');
    }

    // 服务器推：进度事件
    public function progress($user_id , $intent , $msg = '' , $data = null){
        $this->group($user_id , self::SUCCESS , $intent , $msg , $data);
    }

    // 服务器推：完成事件
    public function completed($user_id , $intent , $msg = '' , $data = null){
        $res = $this->group($user_id , self::SUCCESS , $intent , $msg , $data);
        if ($res === false) {
            // 保存用户数据
            $this->redis->taskStatus($user_id , 'completed' , $data);
        } else {
            $this->redis->taskStatus($user_id , 'none');
        }
    }

    // 服务器推：解析错误
    public function parseError($user_id , $intent , $msg = '' , $data = null){
        // 更新任务状态
        $this->redis->taskStatus($user_id , 'none');
        $this->group($user_id , self::ERROR , $intent , $msg , $data);
    }

    // 发送消息：群发
    public function group($user_id , $status , $intent , $msg , $data = null)
    {
        $info = $this->redis->get($this->redis->uk($user_id));
        if ($info === false) {
            return false;
        }
        foreach ($info->conn as $v)
        {
            $this->send($v , $status , $intent , $msg , $data);
        }
    }

    // 发送消息：单发
    public function send($fd , $status , $intent , $msg = '' , $data = null)
    {
        // 发送数据
        switch ($status)
        {
            case self::SUCCESS:
                $this->socket->send($fd , $this->success($intent , $msg , $data));
                break;
            case self::ERROR:
                $this->socket->send($fd , $this->error($intent , $msg , $data));
                break;
            default:
                throw new Exception("不支持的状态");
        }
    }
}

return [

];