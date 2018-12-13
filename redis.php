<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/12/12
 * Time: 10:11
 *
 * redis 测试
 */

$redis = new Redis();
// 单位: 秒
$redis->connect('127.0.0.1' , '6379' , 5);
// 认证
$redis->auth('364793');

$redis->set('name' , 'gravtouch');
$name = $redis->get('name');
var_dump($name);

var_dump($redis->del('name'));

$name = $redis->get('name');
var_dump($name);

