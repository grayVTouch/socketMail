<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/27
 * Time: 15:49
 */

return [
    // 进程池数量
    'worker_count' => 4 ,

    // websocket 连接
    'websocket' => [
        'default' => [
            'host'  => '0.0.0.0' ,
            'port'  => 9009
        ] ,
    ] ,
    // tcp 连接
    'tcp' => [
        'default' => [
            'host' => '0.0.0.0' ,
            'port' => 9008
        ] ,
    ]
];