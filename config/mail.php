<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/20
 * Time: 14:40
 */

return [
    /**
     * ********************
     * 邮箱设置
     * ********************
     */
    // qq 邮箱
    'qq' => [
        'pop' => [
            'server' => 'pop.qq.com' ,
            'ssl_port' => '995',
        ] ,
        'imap' => [
            'server' => 'imap.qq.com' ,
            'ssl_port' => '993'
        ]
    ] ,
    // 126 邮箱
    '126' => [
        'pop' => [
            'server' => 'pop.126.com' ,
            'ssl_port' => '99' ,
        ]
    ]
];