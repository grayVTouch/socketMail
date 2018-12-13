<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/17
 * Time: 15:05
 */

namespace Core\Parser;


trait Parser
{
    public $dom = '';
    public $str = '';
    public $res = [
        // 账单开始日期
        'start_date' => '' ,
        // 账单结束日期
        'end_date' => '' ,
        // 最后还款日
        'last_date' => '' ,
        // 账单金额
        'bill_amount' => '' ,
        // 银行卡号
        'bank_card' => '' ,
        // 发卡行 code
        'bank_code' => '' ,
        // 发卡行名称
        'bank_name' => ''
    ];

    /**
     * **************
     * false 失败
     * true  成功
     * **************
     */
    public $status = true;

    // 日期格式修正
    public function formatDate($date = null){
        if (empty($date)) {
            return null;
        }
        return str_replace('/' , '-' , $date);
    }

    // 获取账单日开始日期
    public function startDate(){
        return $this->res['start_date'];
    }

    // 获取账单结束日期
    public function endDate(){
        return $this->res['end_date'];
    }

    // 获取最后还款日
    public function lastDate(){
        return $this->res['last_date'];
    }

    // 账单金额
    public function billAmount(){
        return $this->res['bill_amount'];
    }

    // 银行卡号
    public function bankCard(){
        return $this->res['bank_card'];
    }

    // 银行卡code
    public function bankCode(){
        return $this->res['bank_code'];
    }

    // 状态判定
    protected function _determine(){
        foreach ($this->res as $k => $v)
        {
            if ($k == 'bill_amount') {
                continue ;
            }
            if (empty($v)) {
                $this->status = false;
                return ;
            }
        }
        $this->status = true;
    }
}