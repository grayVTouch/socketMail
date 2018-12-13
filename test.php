<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/20
 * Time: 14:55
 */
//$mailbox = 'qq';
//$protocol = 'pop';
//$username = 'jfeng.li@qq.com';
//$password = 'snpjaexgamdibijj';
//$attachment_dir = __DIR__ . '/attachment';
//$coding = 'UTF-8';

require __DIR__ . '/app.php';

use Core\App;

// 获取 app 实例

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