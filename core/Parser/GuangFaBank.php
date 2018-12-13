<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/17
 * Time: 14:03
 */

namespace Core\Parser;

use Exception;

class GuangFaBank
{
    use Parser;

    // 这个值是根据不同的页面，按照关键字判断出来的
    private $_len = 7000;

    function __construct($str = ''){
        try {
            $this->str = $str;
            $pos = mb_strpos($this->str , '账单周期');
            $str = mb_substr($this->str , $pos , $this->_len);

            /**
             * *******************
             * 数据正则
             * *******************
             */
            // 日期正则
            $date_reg       = '/\d{4}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])/';
            // 银行卡正则
            $bank_card_reg  = '/\d{4}\*{8}\d{4}/';
            // 金额正则
            $amount_reg     = '/[0-9,]+\.\d{2}/';

            preg_match_all($date_reg , $str , $dates);
            preg_match($bank_card_reg , $str , $bank_cards);
            preg_match_all($amount_reg , $str , $amounts);

            /**
             * *****************
             * 数据处理
             * *****************
             */
            $dates      = $dates[0] ?? [];
            $bank_card  = $bank_cards[0] ?? '';
            $amounts = $amounts[0] ?? [];

            $start_date = $dates[0] ?? null;
            $start_date = $this->formatDate($start_date);
            $end_date   = $dates[1] ?? null;
            $end_date   = $this->formatDate($end_date);
            $last_date  = $dates[2] ?? null;
            $last_date  = $this->formatDate($last_date);
            $bill_amount = $amounts[0] ?? 0;
            $bill_amount = number($bill_amount , 2);

            /**
             * *****************
             * 数据赋值
             * *****************
             */
            $this->res['start_date']    = $start_date;
            $this->res['end_date']      = $end_date;
            $this->res['last_date']     = $last_date;
            $this->res['bill_amount']   = $bill_amount;
            $this->res['bank_card']     = $bank_card;
            $this->res['bank_code']     = 'GDB';
            $this->res['bank_name']     = '广发银行';

            // 务必调用这个方法，不然无法判定是否正确解析
            $this->_determine();
        } catch(Exception $e) {
            // todo 记录日志
            $this->status = false;
        }
    }
}