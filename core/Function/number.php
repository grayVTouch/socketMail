<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/19
 * Time: 16:03
 */

// 保留多少位小数点
function fix_number($num , $len = 0){
    $str = number_format($num , $len);
    $str = preg_replace('/[^0-9\.]/' , '' , $str);
    return floatval($str);
}

// 获取数字
function number($str = '' , $len = 0){
    $str = preg_replace('/[^0-9\.]*/' , '' , $str);
    return fix_number($str , $len);
}