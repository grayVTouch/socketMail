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

use Exception;
use Anonymous;
use Core\Util\MailClient;

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

    // 产生任务的时候
    public function onTask($socket , $task_id , $from_id , $data){
        // 我猜测是在子进程内执行的回调函数
        $user_id = $data['user_id'];
        $intent = $data['intent'];
        $data   = $data['data'];
        switch ($intent)
        {
            case 'parse':
                // 账单解析
                try {
                    $s_time = time();
                    // 邮件解析
                    $mail_client = new MailClient($data->user_mailbox_id , $data->bank_code);
                    // 设置接收到邮件时的回调函数
                    $mail_client->onRecv = function($mail , $index , $count) use($user_id , $intent , $data){
                        echo sprintf("待接收共 %d 封邮件，已经处理 %4d 封；邮件id：%4d；邮件发送人：%s（%s）\n" , $count , $index , $mail->id , $mail->from , $mail->fromName);
                        // 通知客户端进度事件
                        call_user_func([app('websocket_api') , 'progress'] , $user_id , 'progress' , '' , [
                            'index' => $index ,
                            'count' => $count
                        ]);
                    };
                    // 设置邮件接收 + 解析 + 入库完毕时的回调
                    $mail_client->onCompleted = function($total , $filter_by_from , $filter_by_repeat , $success_for_parse , $fail_for_parse) use($s_time , $user_id , $intent , $data){
                        $e_time     = time();
                        $duration   = fix_number(($e_time - $s_time) / 3600 , 2);
                        echo "邮件共 {$total} 封，过滤掉非银行邮件 {$filter_by_from} 封，过滤掉已解析邮件 {$filter_by_repeat} 封，解析成功 {$success_for_parse} 封，解析失败 {$fail_for_parse} 封。\n";
                        echo sprintf("邮件处理完毕，开始时间：%s；结束时间：%s；耗时：%sh（%smin）\n" , date('Y-m-d H:i:s' , $s_time) , date('Y-m-d H:i:s' , $e_time) , $duration , $duration * 60);

                        // todo 更新用户任务状态为已完成
                        // todo 这个需要外部存储支持（进程间通信需要）
                        // todo 建议使用 redis 这类内存型外部存储
                        // 结束任务运行
                        $this->_socket->finish('任务执行完成');
                        // 也许客户端下线了也说不定！
                        // 通知客户端完成
                        call_user_func([app('websocket_api') , 'completed'] , $user_id , 'completed' , '' , compact('total' , 'filter_by_from' , 'filter_by_repeat' , 'success_for_parse' , 'fail_for_parse'));
                    };
                    // 设置接收成功回调函数
                    $mail_client->createMail()
                        ->receiveMail()
                        ->terminate();
                } catch(Exception $e) {
                    call_user_func([app('websocket_api') , '_error'] , $user_id , 'error' , $e->getMessage());
                }
                break;
            default:
                break;
        }
    }

    // 任务结束的时候
    public function onFinish($socket , $task_id , $data){

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