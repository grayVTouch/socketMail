<?php

/*
 * @author grayVTouch
 * @time 2018/11/20
 *
 * 数据库操作
 */

namespace Core\Lib;

use PDO;
use PDOStatement;
use Exception;
use ReflectionObject;

class Database {
	// 数据库连接实例
	protected $_connect		= null;
	// 结果集格式化类型

//    protected  $_fetchType		= PDO::FETCH_ASSOC;
    // 结果集为匿名对象
    protected  $_fetchType		= PDO::FETCH_OBJ;

    // 最后一次查询
    protected $_sql = '';

    /**
     * Database constructor.
     * @param string $db_type 数据库类型
     * @param string $host
     * @param string $db_name 数据库名称
     * @param string $username  用户名
     * @param string $password 密码
     * @param bool $is_persistent 是否持久连接
     * @param string $charset 字符集
     */
	function __construct($db_type = 'mysql' , $host = '127.0.0.1' , $db_name = '' , $username = '' , $password = '' , $is_persistent = false , $charset  =  'utf8'){
		$db_type	 	 = isset($db_type)		   ? $db_type       : 'mysql';
		$host			 = isset($host)		       ? $host		    : '127.0.0.1';
		$is_persistent   = is_bool($is_persistent) ? $is_persistent : false;
        $charset		 = isset($charset)		   ? $charset	    : 'utf8';
        $this->_connect = new PDO($db_type . ":host=" . $host . ";dbname=" . $db_name , $username , $password , [
            PDO::ATTR_PERSISTENT => $is_persistent ,

            // PDO 驱动比较特殊：即使在数据库配置文件中，已经设置了 utf8 字符集。
            // 其实际表现也会是 gbk 字符集
            // 因而需要在运行时指定 utf8 字符集
            PDO::MYSQL_ATTR_INIT_COMMAND => 'set names ' . $charset
        ]);
        // 设置错误时抛出异常
        if ($this->_connect->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            $this->_connect->setAttribute(PDO::ATTR_ERRMODE , PDO::ERRMODE_EXCEPTION);
        }
	}

	/*
	 * 获取链接对象（PDO对象）
	 */
	public function getConnection(){
		return $this->_connect;
	}

	/*
	 * 获取最后插入数据库的一条数据
	 */
	public  function lastInsertId($name = null){
		if (!is_null($name) && !is_string($name)) {
			throw new Exception('参数 1 类型错误');
		}
		return $this->_connect->lastInsertId($name);
	}

	// 以事务 + 预处理语句方式运行 SQL 语句
	private  function _execByTransaction(array $sql = []){
		if (empty($sql)) {
			return true;
		}
		// 开始事务
		$this->_connect->beginTransaction();
		try {
			// 执行sql语句
			foreach ($sql as $v)
            {
                $keys   = array_keys($v);
                $_sql   = $v[$keys[0]];
                $param = $v[$keys[1]];
                $stmt = $this->_connect->prepare($_sql);
                foreach ($param as $k1 => $v1)
                {
                    $k1 = ltrim($k1 , ':');
                    $stmt->bindValue(":{$k1}" , $v1 , $this->type($v1));
                }
                $stmt->execute();
            }
			// 提交事务
			$this->_connect->commit();
		} catch (Exception $excp) {
			// 失败时回滚
			$this->_connect->rollBack();
			// 重新抛出错误信息
			throw new Exception($excp->getMessage());
		}
	}

    // 完整的事务
    public  function transaction(callable $function){
        // 开始事务
        $this->_connect->beginTransaction();
        try {
            $res = call_user_func($function);
            $this->_connect->commit();
            return $res;
        } catch(Exception $excep) {
            // 失败时回滚
            $this->_connect->rollBack();
            // 重新抛出错误信息
            throw new Exception($excep->getMessage());
        }
    }

	// 格式化 PDO 返回的查询结果集
	public  function format(PDOStatement $PDOStatement){
		return $PDOStatement->fetchAll($this->_fetchType);
	}

	// 事务:开启事务
    public function startTrans(){
	    $this->_connect->beginTransaction();
    }

    // 事务：提交事务
    public function commit(){
	    $this->_connect->commit();
    }

    // 事务：回滚事务
    public function rollback(){
	    $this->_connect->rollBack();
    }

    /**
     * @param Mixed $v
     */
    public function type($v){
        if (is_string($v)) {
            return PDO::PARAM_STR;
        }
        if (is_int($v) || is_float($v)) {
            return PDO::PARAM_INT;
        }
        if (is_null($v)) {
            return PDO::PARAM_NULL;
        }
        if (is_bool($v)) {
            return PDO::PARAM_BOOL;
        }
        if (is_resource($v)) {
            return PDO::PARAM_LOB;
        }
        throw new Exception("MySQL 不支持的数据类型");
    }

	// 执行预处理查询
	public function query($sql = '' , array $param = []){
        $stmt = $this->_connect->prepare($sql);
        array_walk($param , function($v , $k) use($stmt){
            $k = ltrim($k , ':');
            $stmt->bindValue(":{$k}" , $v , $this->type($v));
        });
        if (!$stmt->execute()) {
            $err_info = $stmt->errorInfo();
            $err_msg = $this->_errMsg($sql , $err_info);
            throw new Exception($err_msg);
        }
        return $stmt;
	}

	// 查看要执行的预处理语句，实际发送给 mysql 的内容
    public function debug($sql = '' , array $param = []){
        $stmt = $this->_connect->prepare($sql);
        array_walk($param , function($v , $k) use($stmt){
            $k = ltrim($k , ':');
            $stmt->bindValue(":{$k}" , $v , $this->type($v));
        });
        try {
            $this->startTrans();
            // 事务
            $stmt->execute();
            $stmt->debugDumpParams();
            $this->rollback();
        } catch(Exception $e) {
            $stmt->debugDumpParams();
        }
    }

	// 错误提示
    protected function _errMsg($sql , $msg){
        $msg  = "执行SQL语句失败：{$sql}\n";
        $msg .= "SQLState 码：{$msg[0]}\n";
        $msg .= "驱动错误代码：$msg[1]\n";
        $msg .= "错误字符串：$msg[2]";
        return $msg;
    }

	/*
	 * 原生执行获取单行数据，若有多条数据，则只返回其中的第一条数据
	 * 若是获取的记录只有一个字段，则直接返回单元值
	 * 若是获取的记录不止一个字段，则返回整条记录
	 * @param  String $sql      待执行的 SQL 语句
	 * @param  Array  $param   如果是预处理 SQL 语句，则需提供参数
	 * @return Mixed
	 */ 
	public  function get($sql = '' , array $param = []){
		if (!is_string($sql)) {
			throw new Exception('参数 1 类型错误');
		}
		$PDOStatement   = $this->query($sql , $param);
        $res            = $this->format($PDOStatement);
		// 无数据时
		if (empty($res)) {
			return false;
		}
		if (count($res) !== 1) {
			throw new Exception('SQL 语句不合法（只允许返回一行记录）：' . $sql);
		}
		$res = $res[0];
		$reflection_obj = new ReflectionObject($res);
		$attrs = $reflection_obj->getProperties();
        if (count($attrs) === 1) {
            $field = $attrs[0]->name;
            return $res->$field;
        }
        return $res;
	}

	// 返回受影响的记录数
    public function rowCount(){
	    return $this->get('select row_count()');
    }

	/*
	 *原生执行获取所有记录
	 * @param  String $sql      待执行的 SQL 语句
	 * @param  Array  $param   如果是预处理 SQL 语句，则需提供参数
	 * @return Array 
	*/
	public  function getAll($sql = '' , array $param = []){
		if (!is_string($sql)) {
			throw new Exception('参数 1 类型错误');
		}
		$PDOStatement   = $this->query($sql , $param);
		$result         = $this->format($PDOStatement);
		return $result;
	}
}
