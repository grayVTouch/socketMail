<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/30
 * Time: 10:10
 *
 * 响应
 */

namespace Core\Api;


class Response
{
    // 成功数据
    public function success($intent , $msg = '' , $data = null){
        return $this->response('success' ,  $intent , $msg , $data);
    }

    // 失败数据
    public function error($intent , $msg = '' , $data = null){
        return $this->response('error' , $intent , $msg , $data);
    }

    // 响应
    public function response($status , $intent , $msg = '' , $data = null){
        return json_encode(compact('status' , 'msg' , 'intent' , 'data'));
    }
}