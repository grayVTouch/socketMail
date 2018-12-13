<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/26
 * Time: 11:51
 */

$ws = new swoole_websocket_server('0.0.0.0' , 9500);


$ws->on('open' , function($ws , $frame){
    // $ws->send();
});

$ws->on('message' , function(){

});

