<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/7/5
 * Time: 14:43
 */

use Core\App;
use Core\Model\Mailbox;
use Core\Model\MailboxConfig;


// 获取语言包配置文件
function lang($key , $args = []){
    return config(lang_path() , $key , $args);
}

// 获取配置文件
function config($key , $args = []){
    return _config_(config_path() , $key , $args);
}

function base_path(){
    return app()->path()['base'];
}

function config_path(){
    return app()->path()['config'];
}

function core_path(){
    return app()->path()['core'];
}

function common_path(){
    return app()->path()['common'];
}

function lang_path(){
    return app()->path()['lang'];
}

function log_path(){
    return app()->path()['log'];
}

function vendor_path(){
    return app()->path()['vendor'];
}
// 应用实例
function app($classname = ''){
    if (empty($classname)) {
        return App::make('app');
    }
    return App::make($classname);
}

// 数据库实例
function db(){
    return app()->make('db');
}

// socket 实例
function ws(){
    return app()->make('ws');
}

// 支持的邮箱列表
function support_mailbox(){
    static $res = null;
    if (is_null($res)) {
        $mailbox = Mailbox::all();
        $res = [];
        foreach ($mailbox as $v)
        {
            $res[] = $v->name;
        }
    }
    return $res;
}

// 获取邮箱配置
function get_mailbox($mailbox_id , $mailbox , $protocol){
    $mailbox_range = support_mailbox();
    if (!in_array($mailbox , $mailbox_range)) {
        throw new Exception("不支持的邮箱：{$mailbox}");
    }
    $protocol_range = config('app.protocol');
    if (!in_array($protocol , $protocol_range)) {
        throw new Exception("不支持的类型: {$protocol}");
    }
    $protocol = strtolower($protocol);
    $config = MailboxConfig::find($mailbox_id , $protocol);
    $prefix = '{';
    $server = $config->server . ':' . $config->ssl_port;
    $_protocol = '';
    $suffix = '}';
    switch ($protocol)
    {
        case 'pop':
            $_protocol = '/pop3/ssl';
            break;
        case 'imap':
            $_protocol = '/imap/ssl';
            break;
    }
    return $prefix . $server . $_protocol . $suffix;
}

// 根据发件人确定银行
function get_correct_bank_code($from = ''){
    $froms = config('app.from_bank');
    foreach ($froms as $k => $v)
    {
        if ($from == $v) {
            return $k;
        }
    }
    return 'unknow';
}