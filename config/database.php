<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/20
 * Time: 14:38
 */

return [
    'mysql' => [
        // 默认数据库连接
        'default' => [
            'type'      => 'mysql' ,
            'host'      => '127.0.0.1' ,
            'name'      => 'zmzc' ,
//        'name'      => 'cs_db' ,
            'user'      => 'root' ,
            'password'  => '364793' ,
//        'password'  => '123456' ,
            'persistent' => false ,
            'charset'   => 'utf8' ,
            'prefix'    => 'cs_'
        ] ,
        // 默认数据库连接
        'xzm-test' => [
            'type'      => 'mysql' ,
            'host'      => '127.0.0.1' ,
            'name'      => 'cs_db' ,
            'user'      => 'root' ,
            'password'  => '123456' ,
            'persistent' => false ,
            'charset'   => 'utf8' ,
            'prefix'    => 'cs_'
        ] ,
    ] ,

    'redis' => [
        'default' => [
            'host'      => '127.0.0.1' ,
            'port'      => 6379 ,
            'password'  => '364793' ,
            'timeout'   => 0
        ] ,
        'xzm-test' => [
            'host'      => '127.0.0.1' ,
            'port'      => 6379 ,
            'password'  => '' ,
            'timeout'   => 0
        ] ,
    ]


];