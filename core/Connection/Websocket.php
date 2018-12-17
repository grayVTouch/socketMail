<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/30
 * Time: 9:54
 *
 * websocket 连接管理
 */
namespace Core\Connection;


class Websocket
{
    /**
     * @see \swoole_websocket_server
     */
    protected $_socket = null;

    // 客户端连接
    protected $_client = [
        'fd' => [
            // 是否任务中
            'task'      => false
        ]
    ];

    function __construct($socket = null){
        $this->_socket = $socket;
    }

    // 连接打开的时候
    public function onOpen($socket , $frame){
        // 保存连接

    }

    // 接收到消息的时候
    public function onMessage($socket , $frame){
        $api = app('websocket_api');
        $data = json_decode($frame->data);

        // 验证用户登录状态
        if (!$api->verify($frame->fd , $data->intent)) {
            // 关闭连接
            $this->close($frame->fd ,  4001, '用户验证失败');
            return ;
        }
        // 执行用户意图
        call_user_func([$api , $data->intent] , $frame->fd , $data->intent , $data->data ?? []);
    }

    // 连接关闭的时候
    public function onClose($socket , $fd){
        $this->_clear($fd);
    }

    // 关闭连接
    public function close($fd , $code , $msg){
        $this->_clear($fd);
        // 清除用户信息
        $this->_socket->disconnect($fd , $code , $msg);
    }

    // 关闭服务器
    public function shutdown(){
        $this->_socket->shutdown();
    }

    // 用户下线清理
    protected function _clear($fd){
        // 通知客户端用户下线
        app('redis_manager')->offline($fd);
    }

    // 发送数据
    public function send($fd , $data){
        if (!$this->_socket->exist($fd)) {
            // 如果已经下线，不再发送数据
            return ;
        }
        $this->_socket->push($fd , $data);
    }

    // 创建任务
    public function task($data){
        $this->_socket->task($data);
    }
}