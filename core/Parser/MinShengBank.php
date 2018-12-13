<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/16
 * Time: 15:47
 * support:
 *
 * regex by ljf
 */

namespace Core\Parser;

use Exception;
use DateTime;
use DateInterval;

class MinShengBank
{
    use Parser;

    // 匹配正则，by ljf
    // public $reg = '/style=\'font-size:13px;line-height:120%;\'>(\d{4}\/\d{2}\/\d{2})<\/font>[\s\S]*style=\'font-size:13px;line-height:120%;\'>(\d{4}\/\d{2}\/\d{2})[\s\S]*RMB&nbsp;([\d\.\,]*)&nbsp;[\s\S]*RMB&nbsp;([\d\.\,]*)&nbsp;/';
    private $_len = 36825;

    function __construct($str){
        try {
            $this->str = $str;
            // by ljf
            // $res = preg_match($this->reg , $str , $matches);

            $pos = mb_strpos($str , '您的信用卡账户信息');
            $str = mb_substr($str , $pos , $this->_len);
            /**
             * *******************
             * 数据正则
             * *******************
             */
            // 日期正则
            $date_reg       = '/\d{4}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])/';
            // 金额
            $amount_reg     = '/[0-9,]+\.\d{2}/';

            preg_match_all($date_reg , $str , $dates);
            preg_match_all($amount_reg , $str , $amounts);

            /**
             * ********************
             * 数据处理
             * ********************
             */
            $dates      = $dates[0];
            $amounts    = $amounts[0];
            $end_date = $dates[0] ?? null;
            $end_date = $this->formatDate($end_date);
            $last_date = $dates[1] ?? null;
            $last_date = $this->formatDate($last_date);
            if (is_null($end_date)) {
                $start_date = null;
            } else {
                // 开始日期 = 结束日期 - 1d - 1month
                $unix_timestamp = strtotime($end_date);
                $date = date('Y-m-d H:i:s' , $unix_timestamp);
                $date_time = new DateTime($date);
                $date_interval = new DateInterval('P1M1D');
                $start_date = $date_time->sub($date_interval)->format('Y-m-d');
            }
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
            $this->res['bank_code']     = 'CMBC';
            $this->res['bank_name']     = '民生银行';

            // 务必调用这个方法，不然无法判定是否正确解析
            $this->_determine();
        } catch (Exception $e) {
            $this->status = false;
        }
    }

    // 状态判定
    protected function _determine(){
        foreach ($this->res as $k => $v)
        {
            if ($k == 'bank_card' || $k == 'bill_amount') {
                // 特殊，民生银行的电子账单中没有银行卡卡号
                // 特殊，民生银行的电子账单貌似无论是否存在消费行为，都会定期发送电子账单到邮箱，所以，不用理会账单金额 = 0 的情况
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