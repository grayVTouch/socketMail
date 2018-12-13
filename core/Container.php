<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/27
 * Time: 11:40
 */

namespace Core;

use Exception;

class Container
{
    // 注册表
    protected static $_register = [];

    // 获取/注册实例
    public static function make($k = '' , $v = null , $is_singleton = false){
        $register = self::$_register[$k] ?? null;
        if (empty($v)) {
            return is_null($register) ? null : $register['instance'];
        }
        if (!is_null($register) && $register['is_singleton']) {
            throw new Exception('单例不允许重复注册');
        }
        self::$_register[$k] = [
            // 是否单例
            'is_singleton'  => $is_singleton ,
            // 实例
            'instance'      => $v
        ];
    }

    // 获取/注册单例
    public static function singleton($k = '' , $v = null){
        if (empty($v)) {
            return self::make($k);
        }
        self::make($k , $v , true);
    }

    // 删除已注册的实例
    public static function del($key = ''){
        if (isset(self::$_register[$key])) {
            unset(self::$_register[$key]);
        }
    }
}