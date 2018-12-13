<?php

/**
 * **************************************
 * author grayVTouch 2017-11-28
 * 数据库表操作
 * **************************************
 */

namespace Core\Lib;

use Exception;

class Table
{
    // select
    protected $_select = [];

    // join
    protected $_join = [];

    // where
    protected $_where = null;

    // orderBy
    protected $_orderBy = [];

    // groupBy
    protected $_groupBy = null;

    // having
    protected $_having = null;

    // offset
    protected $_offset = null;

    // limit
    protected $_limit = null;

    // in
    protected $_in = [];

    // not in
    protected $_notIn = [];

    // table
    protected $_table = '';

    // Database 实例
    protected $_db = null;

    // 是否开启查询日志
    protected $_enableLog = true;

    // between
    protected $_between = [];

    // not between
    protected $_notBetween = [];

    // 当前执行的 sql 语句（预编译语句）
    protected $_sql = '';

    // 预处理语句参数
    protected $_param = [];

    function __construct(Database $db , $table , $prefix , bool $enable_log = true , $log_dir = ''){
        $this->_db      = $db;
        $this->_table   = $table;
        $this->_prefix  = $prefix;
        $this->_enableLog = $enable_log;

        // 数据库查询日志
        if ($enable_log) {
            $this->_log = new Log($log_dir , 'db' , false);
        }
    }

    // select
    public function select(...$selects){
        $this->_select = $selects;
        return $this;
    }

    // join
    public function join($type , $table , $compare_left , $compare_condition , $compare_right){
        $this->_join[] = [
            'type'              => $type ,
            'table'             => $table ,
            'compare_left'      => $compare_left ,
            'compare_condition' => $compare_condition ,
            'compare_right'     => $compare_right
        ];
    }

    // leftJoin
    public function leftJoin($table , $compare_left , $compare_condition , $compare_right){
        $this->join('left join' , $table , $compare_left , $compare_condition , $compare_right);
        return $this;
    }

    // leftJoin
    public function rightJoin($table , $compare_left , $compare_condition , $compare_right){
        $this->join('right join' , $table , $compare_left , $compare_condition , $compare_right);
        return $this;
    }

    // innerJoin
    public function innerJoin($table , $compare_left , $compare_condition , $compare_right){
        $this->join('inner join' , $table , $compare_left , $compare_condition , $compare_right);
        return $this;
    }

    // orderBy
    public function orderBy($field , $order = 'asc'){
        $this->_orderBy[] = [
            'field' => $field ,
            'order' => $order
        ];
        return $this;
    }

    // groupBy
    public function groupBy($group){
        $this->_groupBy = $group;
        return $this;
    }

    // todo having
    public function having($condition){
        $this->_having = $condition;
        return $this;
    }

    // offset
    public function offset($offset){
        $this->_offset = $offset;
        return $this;
    }

    // limit
    public function limit($limit){
        $this->_limit = $limit;
        return $this;
    }

    // where
    public function where(...$args){
        $len = count($args);
        if ($len === 2) {
            $this->_where = [
                [$args[0] , '=' , $args[1]]
            ];
        } else if ($len == 3) {
            $this->_where = [$args];
        } else {
            $this->_where = $args[0];
        }
        return $this;
    }

    // between
    public function whereBetween($field , ...$args){
        if (empty($args)) {
            return $this;
        }
        $len = count($args);
        if ($len == 1) {
            if (!is_array($args[0])) {
                throw new Exception('参数 2 类型错误');
            }
            $join = 'and';
            $this->_between[] = array_merge([$field] , $args[0] , [$join]);
        } else if ($len == 2) {
            if (is_numeric($args[0])) {
                $join = 'and';
                $this->_between[] = [$field , $args[0] , $args[1] , $join];
            } else if (is_array($args[0])) {
                $join = $args[1] ?? 'and';
                $this->_between[] = array_merge([$field] , $args[0] , [$join]);
            } else {
                throw new Exception('参数 2 类型错误');
            }
        } else if ($len == 3) {
            if (!is_numeric($args[0])) {
                throw new Exception('参数 2 类型错误');
            }

            if (!is_numeric($args[1])) {
                throw new Exception('参数 3 类型错误');
            }

            if (!is_numeric($args[2])) {
                throw new Exception('参数 4 类型错误');
            }
            $this->_between[] = [$field , $args[0] , $args[1] , $args[2]];
        } else {
            throw new Exception('提供的参数数量超过限制');
        }
        return $this;
    }

    // not between
    public function whereNotBetween($field , ...$args){
        if (empty($args)) {
            return $this;
        }
        $len = count($args);
        if ($len == 1) {
            if (!is_array($args[0])) {
                throw new Exception('参数 2 类型错误');
            }
            $join = 'and';
            $this->_notBetween[] = array_merge([$field] , $args[0] , [$join]);
        } else if ($len == 2) {
            if (is_numeric($args[0])) {
                $join = 'and';
                $this->_notBetween[] = [$field , $args[0] , $args[1] , $join];
            } else if (is_array($args[0])) {
                $join = $args[1] ?? 'and';
                $this->_notBetween[] = array_merge([$field] , $args[0] , [$join]);
            } else {
                throw new Exception('参数 2 类型错误');
            }
        } else if ($len == 3) {
            if (!is_numeric($args[0])) {
                throw new Exception('参数 2 类型错误');
            }

            if (!is_numeric($args[1])) {
                throw new Exception('参数 3 类型错误');
            }

            if (!is_numeric($args[2])) {
                throw new Exception('参数 4 类型错误');
            }
            $this->_notBetween[] = [$field , $args[0] , $args[1] , $args[2]];
        } else {
            throw new Exception('提供的参数数量超过限制');
        }
        return $this;
    }

    // 字符串拼接：between
    protected function _between_(){
        if (empty($this->_between)) {
            return ;
        }
        if (empty($this->_where)) {
            $this->_sql .= ' where ';
        } else {
            $last = $this->_where[count($this->_where) - 1];
            $this->_sql .= !isset($last[3]) ? ' and ' : " {$last[3]} ";
        }
        foreach ($this->_between as $k => $v)
        {
            $field          = $this->_safeName($v[0]);
            $condition_one  = md5("between-field-{$k}-one");
            $condition_two  = md5("between-field-{$k}-two");
            $join           = $v[3];
            $this->_sql .= "({$field} between :{$condition_one} and :{$condition_two}) {$join} ";
            $this->_param[$condition_one] = $v[1];
            $this->_param[$condition_two] = $v[2];
        }
        $this->_sql = rtrim($this->_sql , "{$join} ");
    }

    // 字符串拼接：between
    protected function _notBetween_(){
        if (empty($this->_notBetween)) {
            return ;
        }
        if (empty($this->_where)) {
            $this->_sql .= ' where ';
        } else {
            $last = $this->_where[count($this->_where) - 1];
            $this->_sql .= !isset($last[3]) ? ' and ' : " {$last[3]} ";
        }
        foreach ($this->_notBetween as $k => $v)
        {
            $field          = $this->_safeName($v[0]);
            $condition_one  = md5("not-between-field-{$k}-one");
            $condition_two  = md5("not-between-field-{$k}-two");
            $join           = $v[3];
            $this->_sql .= "({$field} not between :{$condition_one} and :{$condition_two}) {$join} ";
            $this->_param[$condition_one] = $v[1];
            $this->_param[$condition_two] = $v[2];
        }
        $this->_sql = rtrim($this->_sql , "{$join} ");
    }

    // 字符串拼接：where
    protected function _where_(){
        if (!empty($this->_where)) {
            $this->_sql .= ' where ';
            // 针对某一个键名的统计情况（出现次数，由于出现次数如果超过了一次，那么在 where 判断的时候可能会出现覆盖的情况）
            $statistics = [];
            array_walk($this->_where , function($v) use(&$keys , &$statistics){
                $statistics[$v[0]]  = 0;
            });
            foreach ($this->_where as $v)
            {
                $field = $this->_safeName($v[0]);
                $statistics[$v[0]]++;
                $key =  md5('_' . $statistics[$v[0]] . '_' . $v[0]);
                $condition = (!isset($v[3]) ? 'and' : $v[3]) . ' ';
                $this->_sql .= $field . ' ' . $v[1] . ' :' . $key . ' ' . $condition;
                $this->_param[$key] = $v[2];
            }
            $this->_sql = rtrim($this->_sql , $condition);
        }
    }

    // 字符串拼接：group by
    protected function _groupBy_(){
        if (!empty($this->_groupBy)) {
            $group_by = $this->_safeName($this->_groupBy);
            $this->_sql .= ' group by ' . $group_by;
        }
    }

    // 字符串拼接：having
    protected function _having_(){
        if (!empty($this->_having)) {
            $this->_sql .= ' having ' . $this->_having;
        }
    }

    // 字符串拼接：order by
    protected  function _orderBy_(){
        if (!empty($this->_orderBy)) {
            $this->_sql .= ' order by';
            foreach ($this->_orderBy as $k => $v)
            {
                $field = $this->_safeName($v['field']);
                $value = md5("order-{$k}");
                $this->_sql  .= " {$field} :{$value} ,";
                $this->_param[$value] = $v['order'];
            }
            $this->_sql = rtrim($this->_sql , ',');
        }
    }

    // 字符串拼接：offset
    protected  function _offset_(){
        if (is_numeric($this->_offset)) {
            $this->_sql .= ' limit :offset , ';
            $this->_param['offset'] = $this->_offset;
        }
    }

    // 字符串拼接：limit
    protected  function _limit_(){
        if (is_numeric($this->_limit)) {
            if (is_numeric($this->_offset)) {
                $this->_sql .= ':limit';
            } else {
                $this->_sql .= ' limit :limit';
            }
            $this->_param['limit'] = $this->_limit;
        }
    }

    // 字符串拼接：join
    protected function _join_(){
        foreach ($this->_join as $v)
        {
            $this->_sql .= ' ' . $v['type'] . ' ' . $this->_prefix .  $v['table'] . ' on ' . $v['compare_left'] . ' ' . $v['compare_condition'] . ' ' . $v['compare_right'];
        }
    }

    // todo 字符串拼接：in
    protected function _in_(){
        if (empty($this->_in)) {
            return ;
        }
        if (empty($this->_where)) {
            $this->_sql .= " where ";
        } else {
            $last = $this->_where[count($this->_where) - 1];
            $this->_sql .= !isset($last[3]) ? ' and ' : " {$last[3]} ";
        }
        $condition = '';
        foreach ($this->_in as $v)
        {
            $field = $this->_safeName($v[0]);
            $condition = $v[3] . ' ';
            $range = implode(' , ' , $v[2]);
            $this->_sql .= $field . ' ' . $v[1] . ' (' . $range . ') ' .  $condition;
        }
        $this->_sql = rtrim($this->_sql , $condition);
    }

    // todo 字符串拼接：not in
    protected function _notIn_(){
        if (empty($this->_notIn)) {
            return ;
        }
        if (empty($this->_where) && empty($this->_in)) {
            $this->_sql .= " where ";
        } else if (empty($this->_where)) {
            $last = $this->_in[count($this->_in) - 1];
            $this->_sql .= ' ' . ($last[3] ?? 'and') . ' ';
        } else {
            $last = $this->_where[count($this->_where) - 1];
            $this->_sql .= !isset($last[3]) ? ' and ' : " {$last[3]} ";
        }
        $condition = '';
        foreach ($this->_notIn as $v)
        {
            $field = $this->_safeName($v[0]);
            $condition = $v[3] . ' ';
            $range = implode(' , ' , $v[2]);
            $this->_sql .= $field . ' ' . $v[1] . ' (' . $range . ') ' . $condition;
        }
        $this->_sql = rtrim($this->_sql , $condition);
    }

    // update：返回受影响的行数
    public function update(array $data = [] , $debug = false , $detail = false){
        if (empty($data)) {
            return ;
        }
        $this->_sql    = 'update ';
        $this->_sql   .= $this->_prefix . $this->_table;
        $this->_join_();
        $this->_sql .= ' set';
        foreach ($data as $k => $v)
        {
            $field = $this->_safeName($k);
            $value = md5('update-' . $k);
            $this->_sql .= sprintf(' %s = :%s ,' , $field  , $value);
            $this->_param[$value] = $v;
        }
        $this->_sql = rtrim($this->_sql , ',');
        // where 字符串拼接
        $this->_where_();
        $this->_between_();
        $this->_notBetween_();
        $this->_in_();
        $this->_notIn_();
        $this->_groupBy_();
        $this->_having_();
        $this->_orderBy_();
        $this->_offset_();
        $this->_limit_();
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        // 记录查询日志
        $this->log();
        $this->_db->query($this->_sql , $this->_param);
        // 返回受影响的行数
        return $this->_db->rowCount();
    }

    // select：获取所有记录
    public function get($debug = false , $detail = false){
        $this->_sql    = 'select ';
        if (empty($this->_select)) {
            $this->_sql .= '*';
        } else {
            $select = array_map(function($v){
                return $this->_safeName($v);
            } , $this->_select);
            $this->_sql .= implode(' , ' , $select);
        }
        $this->_sql .= ' from ';
        $this->_generate();
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        // 记录数据库查询日志
        $this->log();
        return $this->_db->getAll($this->_sql , $this->_param);
    }

    // 获取单条记录
    public function first($debug = false , $detail = false){
        if ($debug) {
            return $this->get(true , $detail);
        }
        return $this->get()[0] ?? null;
    }

    // 获取单条记录中j的单个字段
    public function value($key = '' , $debug = false , $detail = false){
        if ($debug) {
            return $this->first(true , $detail);
        }
        $data = $this->first();
        return is_null($data) ? $data : ($data->$key ?? null);
    }

    // 计算
    protected function cal($type , $name , $amount , $debug , $detail){
        $type_range = ['incr' , 'decr'];
        $type       = in_array($type , $type_range) ? $type : 'incr';
        $this->select($name);
        $origin = $this->value($name , true);
        $origin = floatval($origin);
        $change = $type === 'incr' ? $origin + $amount : $origin - $amount;
        return $this->update([
            $name => $change
        ] , $debug , $detail);
    }

    // 针对单个字段 +1
    public function incr($name , $amount = 1 , $debug = false , $detail = false){
        return $this->cal('incr' , $name , $amount , $debug , $detail);
    }

    // 针对单个字段 -1
    public function decr($name , $amount , $debug = false , $detail = false){
        return $this->cal('decr' , $name , $amount , $debug , $detail);
    }

    // 合计函数
    public function count($debug = false , $detail = false){
        $this->_sql    = 'select count(*) from ';
        $this->_generate();
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        $this->log();
        $res = $this->_db->get($this->_sql , $this->_param);
        return  is_numeric($res) ? (int) $res : $res;
    }

    // 合计函数
    public function sum($column , $debug = false , $detail = false){
        $column = $this->_safeName($column);
        $this->_sql    = "select sum({$column}) from ";
        $this->_generate();
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        $this->log();
        $res = $this->_db->get($this->_sql , $this->_param);
        return  is_numeric($res) ? (float) $res : $res;
    }

    // 平均值函数
    public function avg($column , $debug = false , $detail = false){
        $column = $this->_safeName($column);
        $this->_sql    = "select avg({$column}) from ";
        $this->_generate();
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        $this->log();
        $res = $this->_db->get($this->_sql , $this->_param);
        return  is_numeric($res) ? (float) $res : $res;
    }

    // 最大值
    public function max($column , $debug = false , $detail = false){
        $column = $this->_safeName($column);
        $this->_sql    = "select max({$column}) from ";
        $this->_generate();
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        $this->log();
        return $this->_db->get($this->_sql , $this->_param);
    }

    // 最大值
    public function min($column , $debug = false , $detail = false){
        $column = $this->_safeName($column);
        $this->_sql    = "select min({$column}) from ";
        $this->_generate();
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        $this->log();
        return $this->_db->get($this->_sql , $this->_param);
    }

    // in 操作
    public function in($column , array $range = [] , $join = 'and'){
        if (!empty($range)) {
            $this->_in[] = [$column , 'in' , $range , $join];
        }
        return $this;
    }

    // not in 操作
    public function notIn($column , array $range = [] , $join = 'and'){
        if (!empty($range)) {
            $this->_notIn[] = [$column , 'not in' , $range , $join];
        }
        return $this;
    }

    // 删除
    public function delete($debug = false , $detail = false){
        $this->_sql    = 'delete from ';
        $this->_generate();
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        $this->log();
        $this->_db->query($this->_sql , $this->_param);
        return $this->_db->rowCount();
    }

    // 删除的别名
    public function del($debug = false , $detail = false){
        return $this->delete($debug , $detail);
    }

    // insert，返回受影响的行数
    public function insert(array $data = [] , $debug = false , $detail = false){
        $this->_sql = 'insert into  ';
        $this->_sql .= $this->_prefix . $this->_table;
        $this->_sql .= ' (';
        $keys = array_keys($data);
        array_walk($keys , function(&$v){
            $v = $this->_safeName($v);
        });
        $this->_sql .= implode(' , ' , $keys);
        $this->_sql .= ') values ';
        $this->_sql .= '(';
        foreach ($data as $k => $v)
        {
            $key = md5($k);
            $this->_sql .= ':' . $key . ' ,';
            $this->_param[$key] = $v;
        }
        $this->_sql = rtrim($this->_sql , ',');
        $this->_sql .= ')';
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        $this->log();
        $this->_db->query($this->_sql , $this->_param);
        return $this->_db->rowCount();
    }

    // insertGetId，返回插入记录的id
    public function insertGetId(array $data = [] , $debug = false , $detail = false){
        $this->_sql = 'insert into  ';
        $this->_sql .= $this->_prefix . $this->_table;
        $this->_sql .= ' (';
        $keys = array_keys($data);
        array_walk($keys , function(&$v){
            $v = $this->_safeName($v);
        });
        $this->_sql .= implode(' , ' , $keys);
        $this->_sql .= ') values ';
        $this->_sql .= '(';
        foreach ($data as $k => $v)
        {
            $key = md5($k);
            $this->_sql .= ':' . $key . ' ,';
            $this->_param[$key] = $v;
        }
        $this->_sql = rtrim($this->_sql , ',');
        $this->_sql .= ')';
        if ($debug) {
            if ($detail) {
                return $this->_debug();
            }
            return $this->_sql;
        }
        $this->log();
        $this->_db->query($this->_sql , $this->_param);
        return $this->_db->lastInsertId();
    }

    // 生成 sql 语句
    protected function _generate(){
        $this->_sql  = rtrim($this->_sql , ' ');
        $this->_sql .= ' ';
        $this->_sql .= $this->_prefix . $this->_table;
        $this->_join_();
        $this->_where_();
        $this->_between_();
        $this->_notBetween_();
        $this->_in_();
        $this->_notIn_();
        $this->_groupBy_();
        $this->_having_();
        $this->_orderBy_();
        $this->_offset_();
        $this->_limit_();
    }

    // 记录查询日志
    protected function log(){
        if (!$this->_enableLog) {
            return ;
        }
        $log = sprintf("[%s] %s\r\n" , date('Y-m-d H:i:s') , $this->_sql);
        $this->_log->log($log);
    }

    // 替换预处理语句中的占位符，填充上实际的值
    protected function _debug(){
        $this->_db->debug($this->_sql , $this->_param);
    }

    // 生成安全字段名声
    protected function _safeName($field = ''){
        return sprintf('`%s`' , $field);
    }
}

