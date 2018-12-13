<?php

namespace Core\Lib;

use PDOStatement;
use Exception;
use Core\Lib\Database;

class DBConnection
{
    // 默认数据库链接实例
    protected $_db = null;

    // 数据库表前缀
    protected $_prefix = null;

    // 是否开启查询日志
    protected $_enableLog = false;

    // 日志目录
    protected $_logDir = '';

    /**
     * db_config 的结构请到 database.php 中查看
     */
    function __construct(array $db_config = null , $enable_log = false , $log_dir = ''){
        if (empty($db_config)) {
            throw new Exception('请提供完整的数据连接配置信息');
        }
        $this->_db = new Database($db_config['type'] , $db_config['host'] , $db_config['name'] , $db_config['user'] , $db_config['password'] , $db_config['persistent'] , $db_config['charset']);
        // 数据库表前缀
        $this->_prefix      = $db_config['prefix'];
        $this->_enableLog   = $enable_log;
        $this->_logDir      = $log_dir;
    }

    // 获取 DatabaseTable 对象
    public function table($table){
        return new Table($this->_db , $table , $this->_prefix , $this->_enableLog , $this->_logDir);
    }

    // 更新
    public function update($sql , array $params = []){
        return $this->_db->transaction(function() use($sql , $params){
            $this->_db->query($sql , $params);
            return $this->_db->rowCount();
        });
    }

    // 更新
    public function insert($sql , array $params = []){
        return $this->_db->transaction(function() use($sql , $params){
            $this->_db->query($sql , $params);
            return $this->_db->rowCount();
        });
    }

    // 插入并且获取插入后的数据ID
    public function insertGetId($sql , array $params = []){
        return $this->_db->transaction(function() use($sql , $params){
            $this->_db->query($sql , $params);
            return $this->_db->lastInsertId();
        });
    }

    // 删除
    public function delete($sql , $params){
        return $this->_db->transaction(function() use($sql , $params){
            $this->_db->query($sql , $params);
            return $this->_db->rowCount();
        });
    }

    // 执行语句
    public function statement($sql , array $params = []){
        return $this->_db->query($sql , $params);
    }

    // 获取
    public function select($sql , array $params = []){
        return $this->_db->getAll($sql , $params);
    }

    // 事务
    public function transaction(callable $callback){
        return $this->_db->transaction($callback);
    }

    // 格式化查询结果
    public function format(PDOStatement $stmt){
        return $this->_db->format($stmt);
    }
}