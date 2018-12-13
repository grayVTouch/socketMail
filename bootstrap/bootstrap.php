<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/16
 * Time: 12:06
 */

$root_dir   = str_replace('\\' , '/' , __DIR__ . '/../');
$core_dir   = $root_dir . 'core/';
$common_dir = $root_dir . 'common/';
$vendor_dir = $root_dir . 'vendor/';

require_once $core_dir . 'Lib/Autoload.php';

use Core\Lib\Autoload;

Autoload::register([
    'class' => [
        'Core\\' => $core_dir ,
        '\\'     => $core_dir . 'Class/' ,
    ] ,
    // 加载文件
    'file' => [
        // 基本依赖
        $core_dir . 'Function/base.php' ,
        $core_dir . 'Function/array.php' ,
        $core_dir . 'Function/file.php' ,
        $core_dir . 'Function/string.php' ,
        $core_dir . 'Function/time.php' ,
        $core_dir . 'Function/number.php' ,

        // 系统依赖
        $common_dir . 'currency.php' ,
        $common_dir . 'lib.php' ,
        $common_dir . 'tool.php' ,
    ]
]);

/**
 * *****************
 * 加载第三方依赖
 * *****************
 */

// 邮箱客户端
require_once $vendor_dir . 'php-imap/vendor/autoload.php';
// html 解析
require_once $vendor_dir . 'domCrawler/vendor/autoload.php';

/**
 * **********************
 * 运行程序
 * **********************
 */

// 实例化 App 类
use Core\App;

// 实例化 app
$app = new App();

// 运行程序
$app->run()->terminate();


