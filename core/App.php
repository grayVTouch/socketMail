<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/20
 * Time: 15:31
 */

namespace Core;


use Swoole\WebSocket\Server;

use Core\Connection\Websocket as WebsocketConnection;
use Core\Connection\Worker;
use Core\Lib\Redis;



class App extends Container
{
    protected $path = [];

    function __construct(){
        // 注册路径
        $this->registerPath();

        // 注册基础服务
        $this->registerService();
    }

    // 注册路径
    protected function registerPath(){
        $base = __DIR__ . '/../';
        $base = str_replace('\\' , '/' , $base);
        $base = str_replace('\\\\' , '/' , $base);

        $common = $base . 'common/';
        $config = $base . 'config/';
        $core   = $base . 'core/';
        $lang   = $base . 'lang/';
        $log    = $base . 'log/';
        $vendor = $base . 'vendor/';
        $this->path = compact('base' , 'config' , 'common' , 'core' , 'lang' , 'log' , 'vendor');
    }

    // 获取路径
    public function path(){
        return $this->path;
    }

    // 绑定基础服务
    protected function registerService(){
        // 注册应用实例
        $this->singleton('app' , $this);
        // 主进程注册一个 redis 连接
        $this->singleton('main_redis' , $this->redis());
        // swoole_websocket_server 实例
        $this->singleton('socket' , $this->websocket());
        // Connection\Websocket 实例
        $this->singleton('websocket_connection' , $this->websocketConnection());
        // Connection\Worker
        $this->singleton('worker' , $this->worker());
    }

    // 注册 redis 实例
    public function redis()
    {
        $redis  = config('app.redis');
        $config = config('database.redis');
        $res    = $config[$redis];
        return new Redis($res['host'] , $res['port'] , $res['password'] , $res['timeout']);
    }


    // 注册 socket 连接
    public function websocket(){
        $websocket  = config('app.websocket');
        $config     = config('socket.websocket');
        $res        = $config[$websocket];
        $socket     = new Server($res['host'] , $res['port']);
        // 初始化进程池数量
        $socket->set([
            'task_worker_num' => config('socket.worker_count')
        ]);
        return $socket;
    }

    // 注册 Connection\websocket 实例
    public function websocketConnection(){
        return new WebsocketConnection($this->make('socket'));
    }

    // 注册 Connection/Worker 实例
    public function worker()
    {
        return new Worker($this->make('socket'));
    }

    // 初始化 socket
    protected function initSocket(){
        $socket     = $this->make('socket');
        $websocket  = $this->make('websocket_connection');
        $worker     = $this->make('worker');

        // 主进程内执行的任务
        $socket->on('open'    , [$websocket , 'onOpen']);
        $socket->on('message' , [$websocket , 'onMessage']);
        $socket->on('close'   , [$websocket , 'onClose']);

        // 子进程内执行的任务
        $socket->on('WorkerStart' , [$worker , 'onWorkerStart']);
        $socket->on('task'    , [$worker , 'onTask']);
        $socket->on('finish'  , [$worker , 'onFinish']);
    }

    // 监听端口
    protected function listen(){
        $this->make('socket')->start();
    }

    // 清理 redis 中旧数据
    public function clear()
    {
        $redis = $this->singleton('main_redis');
        // 清理旧数据
        $redis->flushAll();
        // 关闭连接
        $redis->native('close');
        $this->del('main_redis');
    }

    // todo 程序运行
    public function run(){
        $this->clear();
        // 初始化 socket
        $this->initSocket();
        $this->listen();
        return $this;
    }

    // todo 程序运行结束
    public function terminate(){
        // 关闭 websocket
        $this->make('websocket_connection')->shutdown();
    }
}