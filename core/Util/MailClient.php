<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/20
 * Time: 15:31
 *
 * 邮件客户端
 */

namespace Core\Util;

use Exception;

/**
 * ****************
 * 模型（假设的）
 * ****************
 */
use Core\Model\UserMailbox;
use Core\Model\CreditCardBill;
use Core\Model\MailboxLog;

/**
 * ****************************
 * 解析类
 * ****************************
 */
use Core\Parser\MinShengBank;
use Core\Parser\GuangFaBank;
use Core\Parser\NongYeBank;
// todo 这边新增新的邮件解析类

class MailClient
{
    protected $_client = null;
    // 接收到邮件回调
    public $onRecv = null;
    // 解析结束回调
    public $onCompleted = null;
    // 过滤：银行邮件
    public $onFilter = null;
    // 过滤：重复解析邮件
    public $onFilterRepeat = null;
    // 当前正在处理的邮件
    protected $mail = null;
    // 总邮件数
    public $total = 0;
    // 过滤掉邮件数（通过：发件人）
    public $filterByFrom = 0;
    // 过滤掉已解析邮件数（通过：数据库查重）
    public $filterByRepeat = 0;
    // 成功解析邮件数量
    public $successForParse = 0;
    // 失败解析邮件数量
    public $failForParse = 0;
    // 用户邮箱
    public $userMailbox = null;
    // 需要转换编码的邮箱
    protected $_mailboxForGbkToUtf8 = ['ABC'];
    // 银行卡 code
    protected $bankCode = [];

    // 运行时设置
    protected $_ini = [
        // 内存限制
        'memory_limit' => ''
    ];

    function __construct($user_mailbox_id , array $bank_code = [])
    {
        // todo 暂不支持模型
        $mailbox = UserMailbox::get($user_mailbox_id);
        if (is_null($mailbox)) {
            throw new Exception(sprintf("未找到 id = %d 的邮箱记录！实例化邮件客户端失败\n" , date('Y-m-d H:i:s') , $user_mailbox_id));
        }
        $this->userMailbox = $mailbox;
        $this->bankCode = $bank_code;
    }

    // 创建邮件客户端
    public function createMail(){
        $this->_ini['memory_limit'] = ini_get('memory_limit');
        // 初始化时设置内存限制
        ini_set('memory_limit' , '-1');
        $attachment_dir = config('app.attachment_dir');
        $this->_client = new Mail($this->userMailbox->mailbox_id , $this->userMailbox->protocol , $this->userMailbox->username , $this->userMailbox->password , $attachment_dir);
        return $this;
    }

    // 接收邮件
    public function receiveMail(){
        $ids = $this->_client->ids();
        // todo 调试的时候请设置仅抓取 100 封
//        $ids = array_reverse($ids);
//        $ids = array_slice($ids , 0 , 10);
        // 如果正在调试，请直接使用有问题的邮箱id列表！！！！
//        $ids = [];
        $this->total = count($ids);
        array_walk($ids , function($v , $k){
            // 接收邮件
            $mail = $this->_client->mail($v);
            !is_null($this->filterMail($mail)) &&
            !is_null($this->filterRepeat($mail)) &&
            !is_null($bank = $this->parseMail($mail)) &&
            $this->saveMail($mail , $bank);
            if (is_callable($this->onRecv)) {
                call_user_func($this->onRecv , $mail , $k + 1 , $this->total);
            }
        });
        return $this;
    }

    // 过滤邮件
    public function filterMail($mail){
        $mail = $this->_client->filter($mail , $this->bankCode);
        if (is_null($mail)) {
            $this->filterByFrom++;
        }
        return $mail;
    }

    // 过滤重复邮件
    public function filterRepeat($mail){
        $credit_card_bill = CreditCardBill::all($this->userMailbox->id);
        foreach ($credit_card_bill as $v)
        {
            if ($v->mail_id == $mail->mailId) {
                $this->filterByRepeat++;
                return null;
            }
        }
        return $mail;
    }

    // 解析邮件
    public function parseMail($mail){
        switch ($mail->bankCode)
        {
            case 'GDB':
                // 广发银行
                $bank = new GuangFaBank($mail->html);
                break;
            case 'CMBC':
                // 民生银行
                $bank = new MinShengBank($mail->html);
                break;
            case 'ABC':
                // 农业银行
                // 提醒：农业银行的 html，必须转换成 utf8 编码！！
                $mail->html = utf8($mail->html);
                $bank = new NongyeBank($mail->html);
                break;
            default:
                // 请在此处扩充内容
                $bank = false;
        }
        // todo 这段代码基本永远不会执行，但是也不排除不规范操作带来的错误
        // todo 程序稳定后，这段代码可以删除
        if ($bank === false) {
            echo sprintf("暂不支持的邮件：%s（%s）\n" , $mail->from , $mail->fromName);
            return null;
        }
        if (!$bank->status) {
            $this->failForParse++;
            $this->saveErrorLog($mail);
            return null;
        }
        $this->successForParse++;
        echo sprintf("%s：解析发件人：%s（%s）成功\n" , date('Y-m-d H:i:s') , $mail->from , $mail->fromName);
        return $bank;
    }

    // 保存到数据库
    public function saveMail($mail , $bank){
        $data = [
            'mail_id' => $mail->mailId ,
            'user_id' => $this->userMailbox->user_id ,
            'start_date' => $bank->startDate() ,
            'end_date' => $bank->endDate() ,
            'last_date' => $bank->lastDate() ,
            'bill_amount' => $bank->billAmount() ,
            'bank_code' => $bank->bankCode() ,
            'user_mailbox_id' => $this->userMailbox->id
        ];
        CreditCardBill::save($data);
    }

    // 保存错误日志
    protected function saveErrorLog($mail){
        $debug = config('app.debug');
        if ($debug) {
            // 调试模式
            $file = format_path(__DIR__ . '/../template/fail.html');
            file_put_contents($file , $mail->html);
            exit(sprintf("%s：错误；解析发件人：%s（%s，%s）发生错误，解析失败的内容已经保存到 %s 中，请注意查看，程序运行终止！\n" , date('Y-m-d H:i:s') , $mail->fromName , $mail->bankCode , $mail->from , $file));
        }
        // 生产模式
        // 检查时插入还是更新
        $mailbox_log = MailboxLog::get($mail->mailId);
        if (!is_null($mailbox_log)) {
            // 更新错误次数
            MailboxLog::incr($mailbox_log->id);
            $id = $mailbox_log->id;
        } else {
            // 新增
            $id = MailboxLog::save([
                'user_mailbox_id' => $this->userMailbox->id ,
                'bank_code' => $mail->bankCode ,
                'from'      => $mail->from ,
                'content'   => $mail->html ,
                'mail_id'   => $mail->mailId
            ]);
        }
        echo sprintf("Error：%s，解析发件人：%s（%s）发生错误，日志已经保存到 cs_mailbox_log 中，id：{%d}，请注意查看\n" , date('Y-m-d H:i:s') , $mail->fromName , $mail->from , $id);
    }

    // 程序运行结束
    public function terminate(){
        if (is_callable($this->onCompleted)) {
            call_user_func($this->onCompleted , $this->total , $this->filterByFrom , $this->filterByRepeat , $this->successForParse , $this->failForParse);
        }
        // 重置回原来的 php 设置
        ini_set('memory_limit' , $this->_ini['memory_limit']);
    }
}