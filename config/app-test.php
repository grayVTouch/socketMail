<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/16
 * Time: 9:53
 */

return [
    // 应用名称
    'name'  => 'php-project' ,
    // 调试模式
    'debug'     => false ,
    // 数据库
    'mysql'  => 'xzm-test' ,
    // redis 连接
    'redis' => 'xzm-test' ,
    // websocket 连接
    'websocket' => 'default' ,


    /**
     * *********************************
     * 银行相关
     * *********************************
     */
    // 要抓取的邮件的发送人
    'from_bank' => [
        // 广发银行信用卡
        'GDB' => 'creditcard@cgbchina.com.cn' ,
        // 民生银行信用卡
        'CMBC' => 'master@creditcard.cmbc.com.cn' ,
        // 农业银行信用卡
        'ABC' => 'e-statement@creditcard.abchina.com'
    ] ,
    // 邮箱连接类型
    'protocol' => ['pop' , 'imap'] ,
    /**
     * *********************************
     * 分页加载
     * *********************************
     */
    // 分页加载
    'limit' => 10 ,

    /**
     * ********************
     * 邮件相关
     * ********************
     */
    'attachment_dir' => base_path() . 'attachment/'
];