<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/21
 * Time: 10:54
 */

require_once 'app.php';

//$file = 'template/fail.html';
//$str = file_get_contents($file);
//
//use Core\Parser\MinShengBank;
//$res = new MinShengBank($str);
//print_r($res->res);

$count = 0;

$log = db()->table('mailbox_log')->get();

foreach ($log as $v)
{
    $count++;
    $file = "fail_{$v->bank_code}.html";
    file_put_contents($file , $v->content);
}
