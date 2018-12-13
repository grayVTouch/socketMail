<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/20
 * Time: 15:31
 */

namespace Core;

use Core\Api\Websocket;
use swoole_websocket_server;
use swoole_server;

use Exception;
use Core\Lib\DBConnection;
use Core\Connection\Websocket as WebsocketConnection;
use Core\Api\Websocket as WebsocketApi;
use Core\Lib\Redis;
use Core\Util\RedisManager;


class App extends Container
{
    protected $path = [];

    function __construct(){
        // 注册路径
        $this->registerPath();

        // 注册基础服务
        $this->registerService();

        // 这个地方可以做些什么 ...

        // 保存 socket 连接
        $this->socket  = $this->singleton('socket');
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
        // Core\Lib\DBConnection
        $this->singleton('db' , $this->database());
        // Core\Lib\Redis
        $this->singleton('redis' , $this->redis());
        //
        $this->singleton('redis_manager' , $this->redisManager());

        // swoole_websocket_server 实例
        $this->singleton('socket' , $this->websocket());
        // Connection\Websocket 实例
        $this->singleton('websocket_connection' , $this->websocketConnection());
        // Api\Websocket 实例
        $this->singleton('websocket_api' , $this->websocketApi());
    }

    // 创建数据库连接
    public function database(){
        $mysql  = config('app.mysql');
        $config = config('database.mysql');
        $res    = $config[$mysql];
        return new DBConnection($res);
    }

    // 注册 socket 连接
    public function websocket(){
        $websocket  = config('app.websocket');
        $config     = config('socket.websocket');
        $res        = $config[$websocket];
        $socket = new swoole_websocket_server($res['host'] , $res['port']);
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

    // 注册 Api\Websocket 实例
    public function websocketApi(){
        return new WebsocketApi($this->make('websocket_connection') , $this->make('redis_manager'));
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
        return new RedisManager($this->make('redis'));
    }

    // 初始化 socket
    protected function initSocket(){
        $socket     = $this->make('socket');
        $websocket  = $this->make('websocket_connection');

        // 主进程内执行的任务
        $socket->on('open'    , [$websocket , 'onOpen']);
        $socket->on('message' , [$websocket , 'onMessage']);
        $socket->on('close'   , [$websocket , 'onClose']);

        // 子进程内执行的任务
        $socket->on('task'    , [$websocket , 'onTask']);
        $socket->on('finish'  , [$websocket , 'onFinish']);
    }

    // 监听端口
    protected function listen(){
        $this->make('socket')->start();
    }

    // todo 程序运行
    public function run(){
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