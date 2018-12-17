<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/12/17
 * Time: 10:48
 *
 * swoole 子进程
 */

namespace Core\Connection;

use Exception;
use Core\Util\MailClient;
use Core\Lib\Redis;
use Core\Util\RedisManager;
use Core\Lib\DBConnection;
use Core\Api\Websocket as WebsocketApi;
use Swoole\WebSocket\Server;

class Worker
{
    protected $socket = null;

    function __construct(Server $socket)
    {
        $this->socket = $socket;
    }
    
    // 子进程创建的时候调用
    public function onWorkerStart($server , $worker_id)
    {
        // Core\Lib\DBConnection
        app()->singleton('db' , $this->database());
        // Core\Lib\Redis
        app()->singleton('redis' , $this->redis());
        // Core\Util\RedisManager
        app()->singleton('redis_manager' , $this->redisManager());
        // Api\Websocket 实例
        app()->singleton('websocket_api' , $this->websocketApi());
    }

    // 产生任务的时候，工作进程（实际上就是子进程）
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
                        $this->socket->finish('任务执行完成');
                        // 也许客户端下线了也说不定！
                        // 通知客户端完成
                        call_user_func([app('websocket_api') , 'completed'] , $user_id , 'completed' , '' , compact('total' , 'filter_by_from' , 'filter_by_repeat' , 'success_for_parse' , 'fail_for_parse'));
                    };
                    // 设置接收成功回调函数
                    $mail_client->createMail()
                        ->receiveMail()
                        ->terminate();
                } catch(Exception $e) {
                    call_user_func([app('websocket_api') , 'parseError'] , $user_id , 'error' , $e->getMessage());
                }
                break;
            default:
                break;
        }
    }

    // 任务结束的时候
    public function onFinish($socket , $task_id , $data){

    }

    // 创建数据库连接
    public function database(){
        $mysql  = config('app.mysql');
        $config = config('database.mysql');
        $res    = $config[$mysql];
        return new DBConnection($res);
    }

    // 注册 redis 实例
    public function redis()
    {
        $redis  = config('app.redis');
        $config = config('database.redis');
        $res    = $config[$redis];
        return new Redis($res['host'] , $res['port'] , $res['password'] , $res['timeout']);
    }

    // 注册 redis 管理实例
    public function redisManager()
    {
        return new RedisManager(app()->make('redis'));
    }

    // 注册 Api\Websocket 实例
    public function websocketApi(){
        return new WebsocketApi(app()->make('websocket_connection') , app()->make('redis_manager'));
    }
}