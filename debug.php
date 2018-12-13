<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/22
 * Time: 14:01
 */

require_once 'app.php';

use Core\Parser\MinShengBank;
use Core\Parser\GuangFaBank;

$ms = 'log/fail_CMBC.html';
$str = file_get_contents($ms);

$ms_bank = new MinShengBank($str);

echo "民生银行信用卡解析结果：\n";
print_r($ms_bank->res);

$gf = 'log/fail_GDB.html';
$str = file_get_contents($gf);
// echo $str;
$gf_bank = new GuangFaBank($str);

echo "广发银行信用卡解析结果：\n";
print_r($gf_bank->res);
