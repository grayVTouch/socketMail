<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/27
 * Time: 11:13
 */

class A {
    public static $client = null;
}

$server = new Swoole\WebSocket\Server("0.0.0.0", 9501);

$server->set([
    'task_worker_num' => 4
]);

$server->on('open', function (Swoole\WebSocket\Server $server, $request) {
    echo "server: handshake success with fd{$request->fd}\n";
});

$server->on('message', function (Swoole\WebSocket\Server $server, $frame) {

    var_dump($frame->fd);
    var_dump($server->exist($frame->fd));

    $server->task($frame->fd);
});

$server->on('task' , function($server , $task_id , $from_id , $data){
    $time = time();
    while (true)
    {
        $e_time = time();
        $d = $e_time - $time;
        if ($d > 5) {
            var_dump($data);
            var_dump($server->exist($data));
            break;
        }
    }
    $server->finish('');
});

$server->on('close', function ($ser, $fd) {
    echo "client {$fd} closed\n";
});

$server->start();

