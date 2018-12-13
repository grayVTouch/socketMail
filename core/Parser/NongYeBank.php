<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/17
 * Time: 16:06
 */

namespace Core\Parser;

use Exception;

class NongYeBank
{
    use Parser;

    private $_len = 10133;

    function __construct($str){
        try {
            // gbk => utf8
            $this->str = utf8($str);
            $pos = mb_strpos($str , '您的信用卡账户信息');
            $str = mb_substr($str , $pos , $this->_len);
            /**
             * *********************
             * 数据正则
             * *********************
             */
            // 日期正则
            $date_reg = '/[\->]?(\d{8})[\-<]?/';
            // 金额正则
            $amount_reg = '/\d+\.\d{2}/';
            // 卡号正则
            $bank_card_reg = '/\d{5}\*{6}\d{4}/';
            // 匹配
            preg_match_all($date_reg , $str , $dates);
            preg_match_all($amount_reg , $str , $amounts);
            preg_match($bank_card_reg , $str , $banks);

//            print_r($dates);
//            print_r($amounts);
//            print_r($banks);

            /**
             * *********************
             * 数据处理
             * *********************
             */
            $dates      = $dates[1] ?? [];
            $amounts    = $amounts[0] ?? [];
            $bank_card  = $banks[0] ?? '';

            $start_date = $dates[0] ?? null;
            $start_date = $this->formatDate($start_date);
            $end_date   = $dates[1] ?? null;
            $end_date   = $this->formatDate($end_date);
            $last_date  = $dates[2] ?? null;
            $last_date  = $this->formatDate($last_date);
            $bill_amount = $amounts[0] ?? 0;
            $bill_amount = number($bill_amount , 2);


            /**
             * ******************
             * 数据赋值
             * ******************
             */
            $this->res['start_date']    = $start_date;
            $this->res['end_date']      = $end_date;
            $this->res['last_date']     = $last_date;
            $this->res['bill_amount']   = $bill_amount;
            $this->res['bank_card']     = $bank_card;
            $this->res['bank_code']     = 'ABC';
            $this->res['bank_name']     = '农业银行';

            // 务必调用这个方法，不然无法判定是否正确解析
            $this->_determine();
        } catch(Exception $e) {
            $this->status = false;
        }
    }

    // 农业银行信用卡日期处理
    public function formatDate($date = null){
        if (empty($date)) {
            return null;
        }
        $year   = substr($date , 0 , 4);
        $month  = substr($date , 4 , 2);
        $_date  = substr($date , 6 , 2);
        return sprintf("%s-%s-%s" , $year , $month , $_date);
    }
}