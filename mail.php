<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/28
 * Time: 11:29
 */

require_once __DIR__ . '/vendor/mail/app.php';

use swoole_client;
use Core\App;


// 创建 tcp 客户端
$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

//注册连接成功回调
$client->on("connect", function($cli) {

});

//注册数据接收回调
$client->on("receive", function($cli, $data){
    echo "接收到服务器数据：{$data}\n";
});

//注册连接失败回调
$client->on("error", function($cli){
    echo "Connect failed\n";
});

//注册连接关闭回调
$client->on("close", function($cli){
    echo "Connection close\n";
});

//发起连接
$client->connect('0.0.0.0', 9009, 0.5);



// 获取 app 实例
$app = new App();

$s_time = time();

// 设置接收到邮件时的回调函数
$app->onRecv = function($mail , $index , $count){
    echo sprintf("总共 %d 封邮件，已经接收 %4d 封；邮件id：%4d；邮件发送人：%s（%s）\n" , $count , $index , $mail->id , $mail->from , $mail->fromName);
};

// 设置邮件接收 + 解析 + 入库完毕时的回调
$app->onCompleted = function($total , $fail) use($s_time){
    $e_time = time();
    $duration = number_format(($e_time - $s_time) / 3600 , 2) . 'h';
    echo sprintf("邮件解析完毕，开始时间：%s；结束时间：%s；耗时：%s\n" , date('Y-m-d H:i:s' , $s_time) , date('Y-m-d H:i:s' , $e_time) , $duration);
    echo "总共 {$total} 封邮件，失败 {$fail} 封。\n";
};

// 仅需要提供一个 邮箱 id
$user_mailbox_id = 1;

// 开始处理
$app->createMail($user_mailbox_id)
    ->receiveMail()
    ->filterMail()
    ->filterRepeat()
    ->parseMail()
    ->terminate();