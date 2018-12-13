<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/20
 * Time: 17:42
 */

namespace Core\Model;


class CreditCardBill
{
    // 获取所有
    public static function all($user_mailbox_id = 0){
        return db()->table('credit_card_bill')->where('user_mailbox_id' , $user_mailbox_id)->get();
    }

    // 保存
    public static function save($data){
        return db()->table('credit_card_bill')->insertGetId($data);
    }
}