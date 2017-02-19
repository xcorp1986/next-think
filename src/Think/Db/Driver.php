<?php
    
    
    namespace Think\Db;
    
    use PDO;
    use PDOException;


    /**
     * Class Driver
     * @package Think\Db
     * @method array getFields(string $tableName) 取得数据表的字段信息
     * @method  array getTables(string $dbName = '') 取得数据库的表信息
     * @method  mixed insertAll(array $dataSet, $options = [], $replace = false) 批量插入记录
     */
    abstract class Driver
    {
        /**
         * @var \PDOStatement $PDOStatement PDO操作实例
         */
        protected $PDOStatement = null;
        // 当前操作所属的模型名
        protected $model = '_think_';
        // 当前SQL指令
        protected $queryStr = '';
        protected $modelSql = [];
        // 最后插入ID
        protected $lastInsID = null;
        // 返回或者影响记录数
        protected $numRows = 0;
        // 事务指令数
        protected $transTimes = 0;
        // 错误信息
        protected $error = '';
        // 数据库连接ID 支持多个连接
        protected $linkID = [];
        /**
         * @var \PDO $_linkID 当前连接ID
         */
        protected $_linkID = null;
        // 数据库连接参数配置
        protected $config = [
            'type'           => '',     // 数据库类型
            'hostname'       => '127.0.0.1', // 服务器地址
            'database'       => '',          // 数据库名
            'username'       => '',      // 用户名
            'password'       => '',          // 密码
            'hostport'       => '',        // 端口
            'dsn'            => '', //
            'params'         => [], // 数据库连接参数
            'charset'        => 'utf8',      // 数据库编码默认采用utf8
            'prefix'         => '',    // 数据库表前缀
            'debug'          => false, // 数据库调试模式
            'deploy'         => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'rw_separate'    => false,       // 数据库读写是否分离 主从式有效
            'master_num'     => 1, // 读写分离后 主服务器数量
            'slave_no'       => '', // 指定从服务器序号
            'db_like_fields' => '',
        ];
        // 数据库表达式
        protected $exp = [
            'eq'          => '=',
            'neq'         => '<>',
            'gt'          => '>',
            'egt'         => '>=',
            'lt'          => '<',
            'elt'         => '<=',
            'notlike'     => 'NOT LIKE',
            'like'        => 'LIKE',
            'in'          => 'IN',
            'notin'       => 'NOT IN',
            'not in'      => 'NOT IN',
            'between'     => 'BETWEEN',
            'not between' => 'NOT BETWEEN',
            'notbetween'  => 'NOT BETWEEN',
        ];
        // 查询表达式
        protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%LOCK%%COMMENT%';
        // 查询次数
        protected $queryTimes = 0;
        // 执行次数
        protected $executeTimes = 0;
        // PDO连接参数
        protected $options = [
            PDO::ATTR_CASE              => PDO::CASE_NATURAL,
            PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];
        // 参数绑定
        protected $bind = [];
        
        /**
         * 读取数据库配置信息
         * @access public
         *
         * @param array $config 数据库配置数组
         */
        public function __construct(array $config = [])
        {
            if ( ! empty($config)) {
                $this->config = array_merge($this->config, $config);
                if (is_array($this->config['params'])) {
                    $this->options = $this->config['params'] + $this->options;
                }
            }
        }
        
        /**
         * 启动事务
         * @access public
         * @return mixed
         */
        public function startTrans()
        {
            $this->initConnect();
            if ( ! $this->_linkID) {
                return false;
            }
            //数据rollback 支持
            if ($this->transTimes == 0) {
                $this->_linkID->beginTransaction();
            }
            $this->transTimes++;
            
            return;
        }
        
        /**
         * 初始化数据库连接
         * @access protected
         * @return void
         */
        protected function initConnect()
        {
            // 默认单数据库
            if ( ! $this->_linkID) {
                $this->_linkID = $this->connect();
            }
        }
        
        /**
         * 连接数据库方法
         * @access public
         *
         * @param string $config
         * @param int    $linkNum
         * @param bool   $autoConnection
         *
         * @throws PDOException
         * @return mixed|\PDO
         */
        public function connect($config = '', $linkNum = 0, $autoConnection = false)
        {
            if ( ! isset($this->linkID[$linkNum])) {
                if (empty($config)) {
                    $config = $this->config;
                }
                try {
                    if (empty($config['dsn'])) {
                        $config['dsn'] = $this->parseDsn($config);
                    }
                    $this->linkID[$linkNum] = new PDO(
                        $config['dsn'],
                        $config['username'],
                        $config['password'],
                        $this->options
                    );
                } catch (PDOException $e) {
                    if ($autoConnection) {
                        trace($e->getMessage(), '', 'ERR');
                        
                        return $this->connect($autoConnection, $linkNum);
                    } elseif ($config['debug']) {
                        E($e->getMessage());
                    }
                }
            }
            
            return $this->linkID[$linkNum];
        }
        
        /**
         * 解析pdo连接的dsn信息
         * @access public
         *
         * @param array $config 连接信息
         *
         * @return string
         */
        protected function parseDsn(array $config = [])
        {
        }
        
        /**
         * 用于非自动提交状态下面的查询提交
         * @access public
         * @return bool
         */
        public function commit()
        {
            if ($this->transTimes > 0) {
                $result           = $this->_linkID->commit();
                $this->transTimes = 0;
                if ( ! $result) {
                    $this->error();
                    
                    return false;
                }
            }
            
            return true;
        }
        
        /**
         * 数据库错误信息
         * 并显示当前的SQL语句
         * @access public
         * @return string
         */
        public function error()
        {
            if ($this->PDOStatement) {
                $error       = $this->PDOStatement->errorInfo();
                $this->error = $error[1].':'.$error[2];
            } else {
                $this->error = '';
            }
            if ('' != $this->queryStr) {
                $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
            }
            // 记录错误日志
            trace($this->error, '', 'ERR');
            // 开启数据库调试模式
            if ($this->config['debug']) {
                E($this->error);
            } else {
                return $this->error;
            }
        }
        
        /**
         * 事务回滚
         * @access public
         * @return bool
         */
        public function rollback()
        {
            if ($this->transTimes > 0) {
                $result           = $this->_linkID->rollBack();
                $this->transTimes = 0;
                if ( ! $result) {
                    $this->error();
                    
                    return false;
                }
            }
            
            return true;
        }
        
        /**
         * 获得查询次数
         * @access public
         *
         * @param bool $execute 是否包含所有查询
         *
         * @return int
         */
        public function getQueryTimes($execute = false)
        {
            return $execute ? $this->queryTimes + $this->executeTimes : $this->queryTimes;
        }
        
        /**
         * 获得执行次数
         * @access public
         * @return int
         */
        public function getExecuteTimes()
        {
            return $this->executeTimes;
        }
        
        /**
         * 插入记录
         * @access public
         *
         * @param array $data    数据
         * @param array $options 参数表达式
         * @param bool  $replace 是否replace
         *
         * @return false | int
         */
        public function insert(array $data, array $options = [], $replace = false)
        {
            $values      = $fields = [];
            $this->model = $options['model'];
            $this->parseBind(! empty($options['bind']) ? $options['bind'] : []);
            foreach ($data as $key => $val) {
                if (is_array($val) && 'exp' == $val[0]) {
                    $fields[] = $this->parseKey($key);
                    $values[] = $val[1];
                } elseif (is_null($val)) {
                    $fields[] = $this->parseKey($key);
                    $values[] = 'NULL';
                    // 过滤非标量数据
                } elseif (is_scalar($val)) {
                    $fields[] = $this->parseKey($key);
                    if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
                        $values[] = $this->parseValue($val);
                    } else {
                        $name     = count($this->bind);
                        $values[] = ':'.$name;
                        $this->bindParam($name, $val);
                    }
                }
            }
            // 兼容数字传入方式
            $replace = (is_numeric($replace) && $replace > 0) ? true : $replace;
            $sql     = (true === $replace ? 'REPLACE' : 'INSERT').' INTO '.$this->parseTable(
                    $options['table']
                ).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')'.$this->parseDuplicate($replace);
            $sql .= $this->parseComment(! empty($options['comment']) ? $options['comment'] : '');
            
            return $this->execute($sql, ! empty($options['fetch_sql']));
        }
        
        /**
         * 参数绑定分析
         * @access protected
         *
         * @param array $bind
         */
        protected function parseBind(array $bind = [])
        {
            $this->bind = array_merge($this->bind, $bind);
        }
        
        /**
         * 字段名分析
         * @access protected
         *
         * @param string $key
         *
         * @return string
         */
        protected function parseKey(&$key)
        {
            return $key;
        }
        
        /**
         * value分析
         * @access protected
         *
         * @param mixed $value
         *
         * @return string
         */
        protected function parseValue($value)
        {
            if (is_string($value)) {
                $value = strpos($value, ':') === 0 && in_array($value, array_keys($this->bind)) ? $this->escapeString(
                    $value
                ) : '\''.$this->escapeString($value).'\'';
            } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
                $value = $this->escapeString($value[1]);
            } elseif (is_array($value)) {
                $value = array_map([$this, 'parseValue'], $value);
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_null($value)) {
                $value = 'null';
            }
            
            return $value;
        }
        
        /**
         * SQL指令安全过滤
         * @access public
         *
         * @param string $str SQL字符串
         *
         * @return string
         */
        public function escapeString($str)
        {
            return addslashes($str);
        }
        
        /**
         * 参数绑定
         * @access protected
         *
         * @param string $name  绑定参数名
         * @param mixed  $value 绑定值
         *
         * @return void
         */
        protected function bindParam($name, $value)
        {
            $this->bind[':'.$name] = $value;
        }
        
        /**
         * table分析
         * @access   protected
         *
         * @param string|array $tables
         *
         * @return string
         */
        protected function parseTable($tables)
        {
            // 支持别名定义
            if (is_array($tables)) {
                $array = [];
                foreach ($tables as $table => $alias) {
                    if ( ! is_numeric($table)) {
                        $array[] = $this->parseKey($table).' '.$this->parseKey($alias);
                    } else {
                        $array[] = $this->parseKey($alias);
                    }
                }
                $tables = $array;
            } elseif (is_string($tables)) {
                $tables = explode(',', $tables);
                array_walk($tables, [&$this, 'parseKey']);
            }
            
            return implode(',', $tables);
        }
        
        /**
         * ON DUPLICATE KEY UPDATE 分析
         * @access protected
         *
         * @param mixed $duplicate
         *
         * @return string
         */
        protected function parseDuplicate($duplicate)
        {
            return '';
        }
        
        /**
         * comment分析
         * @access protected
         *
         * @param string $comment
         *
         * @return string
         */
        protected function parseComment($comment)
        {
            return ! empty($comment) ? ' /* '.$comment.' */' : '';
        }
        
        /**
         * 执行语句
         * @access public
         *
         * @param string $str      sql指令
         * @param bool   $fetchSql 不执行只是获取SQL
         *
         * @throws \PDOException
         * @return mixed
         */
        public function execute($str, $fetchSql = false)
        {
            $this->initConnect();
            if ( ! $this->_linkID) {
                return false;
            }
            $this->queryStr = $str;
            if ( ! empty($this->bind)) {
                $that           = $this;
                $this->queryStr = strtr(
                    $this->queryStr,
                    array_map(
                        function ($val) use ($that) {
                            return '\''.$that->escapeString($val).'\'';
                        },
                        $this->bind
                    )
                );
            }
            if ($fetchSql) {
                return $this->queryStr;
            }
            //释放前次的查询结果
            if ( ! empty($this->PDOStatement)) {
                $this->free();
            }
            $this->executeTimes++;
            // 记录写入操作
//            N('db_write', 1);
            // 记录开始执行时间
            $this->debug(true);
            $this->PDOStatement = $this->_linkID->prepare($str);
            if (false === $this->PDOStatement) {
                $this->error();
                
                return false;
            }
            foreach ($this->bind as $key => $val) {
                if (is_array($val)) {
                    $this->PDOStatement->bindValue($key, $val[0], $val[1]);
                } else {
                    $this->PDOStatement->bindValue($key, $val);
                }
            }
            $this->bind = [];
            try {
                $result = $this->PDOStatement->execute();
                // 调试结束
                $this->debug(false);
                if (false === $result) {
                    $this->error();
                    
                    return false;
                } else {
                    $this->numRows = $this->PDOStatement->rowCount();
                    if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                        $this->lastInsID = $this->_linkID->lastInsertId();
                    }
                    
                    return $this->numRows;
                }
            } catch (PDOException $e) {
                $this->error();
                
                return false;
            }
        }
        
        /**
         * 释放查询结果
         * @access public
         */
        public function free()
        {
            $this->PDOStatement = null;
        }
        
        /**
         * 数据库调试 记录当前SQL
         * @access protected
         *
         * @param bool $start 调试开始标记 true 开始 false 结束
         */
        protected function debug($start)
        {
            // 开启数据库调试模式
            if ($this->config['debug']) {
                if ($start) {
                    G('queryStartTime');
                } else {
                    $this->modelSql[$this->model] = $this->queryStr;
                    // 记录操作结束时间
                    G('queryEndTime');
                    trace($this->queryStr.' [ RunTime:'.G('queryStartTime', 'queryEndTime').'s ]', '', 'SQL');
                }
            }
        }
        
        /**
         * 通过Select方式插入记录
         * @access   public
         *
         * @param string $fields  要插入的数据表字段名
         * @param string $table   要插入的数据表名
         * @param array  $options 查询数据参数
         *
         * @return false|int
         */
        public function selectInsert($fields, $table, array $options = [])
        {
            $this->model = $options['model'];
            $this->parseBind(! empty($options['bind']) ? $options['bind'] : []);
            if (is_string($fields)) {
                $fields = explode(',', $fields);
            }
            array_walk($fields, [$this, 'parseKey']);
            $sql = 'INSERT INTO '.$this->parseTable($table).' ('.implode(',', $fields).') ';
            $sql .= $this->buildSelectSql($options);
            
            return $this->execute($sql, ! empty($options['fetch_sql']));
        }
        
        /**
         * 生成查询SQL
         * @access public
         *
         * @param array $options 表达式
         *
         * @return string
         */
        public function buildSelectSql(array $options = [])
        {
            if (isset($options['page'])) {
                // 根据页数计算limit
                list($page, $listRows) = $options['page'];
                $page             = $page > 0 ? $page : 1;
                $listRows         = $listRows > 0 ? $listRows : (is_numeric(
                    $options['limit']
                ) ? $options['limit'] : 20);
                $offset           = $listRows * ($page - 1);
                $options['limit'] = $offset.','.$listRows;
            }
            
            return $this->parseSql($this->selectSql, $options);
        }
        
        /**
         * 替换SQL语句中表达式
         * @access public
         *
         * @param  string $sql
         * @param array   $options 表达式
         *
         * @return string
         */
        public function parseSql($sql = '', array $options = [])
        {
            return str_replace(
                [
                    '%TABLE%',
                    '%DISTINCT%',
                    '%FIELD%',
                    '%JOIN%',
                    '%WHERE%',
                    '%GROUP%',
                    '%HAVING%',
                    '%ORDER%',
                    '%LIMIT%',
                    '%UNION%',
                    '%LOCK%',
                    '%COMMENT%',
                    '%FORCE%',
                ],
                [
                    $this->parseTable($options['table']),
                    $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false),
                    $this->parseField(! empty($options['field']) ? $options['field'] : '*'),
                    $this->parseJoin(! empty($options['join']) ? $options['join'] : ''),
                    $this->parseWhere(! empty($options['where']) ? $options['where'] : ''),
                    $this->parseGroup(! empty($options['group']) ? $options['group'] : ''),
                    $this->parseHaving(! empty($options['having']) ? $options['having'] : ''),
                    $this->parseOrder(! empty($options['order']) ? $options['order'] : ''),
                    $this->parseLimit(! empty($options['limit']) ? $options['limit'] : ''),
                    $this->parseUnion(! empty($options['union']) ? $options['union'] : ''),
                    $this->parseLock(isset($options['lock']) ? $options['lock'] : false),
                    $this->parseComment(! empty($options['comment']) ? $options['comment'] : ''),
                    $this->parseForce(! empty($options['force']) ? $options['force'] : ''),
                ],
                $sql
            );
        }
        
        /**
         * distinct分析
         * @access protected
         *
         * @param mixed $distinct
         *
         * @return string
         */
        protected function parseDistinct($distinct)
        {
            return ! empty($distinct) ? ' DISTINCT ' : '';
        }
        
        /**
         * field分析
         * @access protected
         *
         * @param mixed $fields
         *
         * @return string
         */
        protected function parseField($fields)
        {
            if (is_string($fields) && '' !== $fields) {
                $fields = explode(',', $fields);
            }
            if (is_array($fields)) {
                // 完善数组方式传字段名的支持
                // 支持 'field1'=>'field2' 这样的字段别名定义
                $array = [];
                foreach ($fields as $key => $field) {
                    if ( ! is_numeric($key)) {
                        $array[] = $this->parseKey($key).' AS '.$this->parseKey($field);
                    } else {
                        $array[] = $this->parseKey($field);
                    }
                }
                $fieldsStr = implode(',', $array);
            } else {
                $fieldsStr = '*';
            }
            
            //TODO 如果是查询全部字段，并且是join的方式，那么就把要查的表加个别名，以免字段被覆盖
            return $fieldsStr;
        }
        
        /**
         * join分析
         * @access protected
         *
         * @param mixed $join
         *
         * @return string
         */
        protected function parseJoin($join)
        {
            $joinStr = '';
            if ( ! empty($join)) {
                $joinStr = ' '.implode(' ', $join).' ';
            }
            
            return $joinStr;
        }
        
        /**
         * where分析
         * @access protected
         *
         * @param string|array $where
         *
         * @return string
         */
        protected function parseWhere($where)
        {
            $whereStr = '';
            if (is_string($where)) {
                // 直接使用字符串条件
                $whereStr = $where;
            } else { // 使用数组表达式
                $operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
                if (in_array($operate, ['AND', 'OR', 'XOR'])) {
                    // 定义逻辑运算规则 例如 OR XOR AND NOT
                    $operate = ' '.$operate.' ';
                    unset($where['_logic']);
                } else {
                    // 默认进行 AND 运算
                    $operate = ' AND ';
                }
                foreach ($where as $key => $val) {
                    if (is_numeric($key)) {
                        $key = '_complex';
                    }
                    if (0 === strpos($key, '_')) {
                        // 解析特殊条件表达式
                        $whereStr .= $this->parseThinkWhere($key, $val);
                    } else {
                        // 查询字段的安全过滤
                        // if(!preg_match('/^[A-Z_\|\&\-.a-z0-9\(\)\,]+$/',trim($key))){
                        //     E(L('_EXPRESS_ERROR_').':'.$key);
                        // }
                        // 多条件支持
                        $multi = is_array($val) && isset($val['_multi']);
                        $key   = trim($key);
                        // 支持 name|title|nickname 方式定义查询字段
                        if (strpos($key, '|')) {
                            $array = explode('|', $key);
                            $str   = [];
                            foreach ($array as $m => $k) {
                                $v     = $multi ? $val[$m] : $val;
                                $str[] = $this->parseWhereItem($this->parseKey($k), $v);
                            }
                            $whereStr .= '( '.implode(' OR ', $str).' )';
                        } elseif (strpos($key, '&')) {
                            $array = explode('&', $key);
                            $str   = [];
                            foreach ($array as $m => $k) {
                                $v     = $multi ? $val[$m] : $val;
                                $str[] = '('.$this->parseWhereItem($this->parseKey($k), $v).')';
                            }
                            $whereStr .= '( '.implode(' AND ', $str).' )';
                        } else {
                            $whereStr .= $this->parseWhereItem($this->parseKey($key), $val);
                        }
                    }
                    $whereStr .= $operate;
                }
                $whereStr = substr($whereStr, 0, -strlen($operate));
            }
            
            return empty($whereStr) ? '' : ' WHERE '.$whereStr;
        }
        
        /**
         * 特殊条件分析
         * @access protected
         *
         * @param string $key
         * @param mixed  $val
         *
         * @return string
         */
        protected function parseThinkWhere($key, $val)
        {
            $whereStr = '';
            switch ($key) {
                case '_string':
                    // 字符串模式查询条件
                    $whereStr = $val;
                    break;
                case '_complex':
                    // 复合查询条件
                    $whereStr = substr($this->parseWhere($val), 6);
                    break;
                case '_query':
                    // 字符串模式查询条件
                    parse_str($val, $where);
                    if (isset($where['_logic'])) {
                        $op = ' '.strtoupper($where['_logic']).' ';
                        unset($where['_logic']);
                    } else {
                        $op = ' AND ';
                    }
                    $array = [];
                    foreach ($where as $field => $data) {
                        $array[] = $this->parseKey($field).' = '.$this->parseValue($data);
                    }
                    $whereStr = implode($op, $array);
                    break;
                default:
                    break;
            }
            
            return '( '.$whereStr.' )';
        }
        
        /**
         * where子单元解析
         *
         * @param $key
         * @param $val
         *
         * @return string
         */
        protected function parseWhereItem($key, $val)
        {
            $whereStr = '';
            if (is_array($val)) {
                if (is_string($val[0])) {
                    $exp = strtolower($val[0]);
                    // 比较运算
                    if (preg_match('/^(eq|neq|gt|egt|lt|elt)$/', $exp)) {
                        $whereStr .= $key.' '.$this->exp[$exp].' '.$this->parseValue($val[1]);
                        // 模糊查找
                    } elseif (preg_match('/^(notlike|like)$/', $exp)) {
                        if (is_array($val[1])) {
                            $likeLogic = isset($val[2]) ? strtoupper($val[2]) : 'OR';
                            if (in_array($likeLogic, ['AND', 'OR', 'XOR'])) {
                                $like = [];
                                foreach ($val[1] as $item) {
                                    $like[] = $key.' '.$this->exp[$exp].' '.$this->parseValue($item);
                                }
                                $whereStr .= '('.implode(' '.$likeLogic.' ', $like).')';
                            }
                        } else {
                            $whereStr .= $key.' '.$this->exp[$exp].' '.$this->parseValue($val[1]);
                        }
                        // 使用表达式
                    } elseif ('bind' == $exp) {
                        $whereStr .= $key.' = :'.$val[1];
                        // 使用表达式
                    } elseif ('exp' == $exp) {
                        $whereStr .= $key.' '.$val[1];
                        // IN 运算
                    } elseif (preg_match('/^(notin|not in|in)$/', $exp)) {
                        if (isset($val[2]) && 'exp' == $val[2]) {
                            $whereStr .= $key.' '.$this->exp[$exp].' '.$val[1];
                        } else {
                            if (is_string($val[1])) {
                                $val[1] = explode(',', $val[1]);
                            }
                            $zone = implode(',', $this->parseValue($val[1]));
                            $whereStr .= $key.' '.$this->exp[$exp].' ('.$zone.')';
                        }
                        // BETWEEN运算
                    } elseif (preg_match('/^(notbetween|not between|between)$/', $exp)) {
                        $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                        $whereStr .= $key.' '.$this->exp[$exp].' '.$this->parseValue(
                                $data[0]
                            ).' AND '.$this->parseValue($data[1]);
                    } else {
                        E(L('_EXPRESS_ERROR_').':'.$val[0]);
                    }
                } else {
                    $count = count($val);
                    $rule  = isset($val[$count - 1]) ? (is_array($val[$count - 1]) ? strtoupper(
                        $val[$count - 1][0]
                    ) : strtoupper($val[$count - 1])) : '';
                    if (in_array($rule, ['AND', 'OR', 'XOR'])) {
                        $count = $count - 1;
                    } else {
                        $rule = 'AND';
                    }
                    for ($i = 0; $i < $count; $i++) {
                        $data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
                        if ('exp' == strtolower($val[$i][0])) {
                            $whereStr .= $key.' '.$data.' '.$rule.' ';
                        } else {
                            $whereStr .= $this->parseWhereItem($key, $val[$i]).' '.$rule.' ';
                        }
                    }
                    $whereStr = '( '.substr($whereStr, 0, -4).' )';
                }
            } else {
                //对字符串类型字段采用模糊匹配
                $likeFields = $this->config['db_like_fields'];
                if ($likeFields && preg_match('/^('.$likeFields.')$/i', $key)) {
                    $whereStr .= $key.' LIKE '.$this->parseValue('%'.$val.'%');
                } else {
                    $whereStr .= $key.' = '.$this->parseValue($val);
                }
            }
            
            return $whereStr;
        }
        
        /**
         * group分析
         * @access protected
         *
         * @param mixed $group
         *
         * @return string
         */
        protected function parseGroup($group)
        {
            return ! empty($group) ? ' GROUP BY '.$group : '';
        }
        
        /**
         * having分析
         * @access protected
         *
         * @param string $having
         *
         * @return string
         */
        protected function parseHaving($having)
        {
            return ! empty($having) ? ' HAVING '.$having : '';
        }
        
        /**
         * order分析
         * @access protected
         *
         * @param mixed $order
         *
         * @return string
         */
        protected function parseOrder($order)
        {
            if (is_array($order)) {
                $array = [];
                foreach ($order as $key => $val) {
                    if (is_numeric($key)) {
                        $array[] = $this->parseKey($val);
                    } else {
                        $array[] = $this->parseKey($key).' '.$val;
                    }
                }
                $order = implode(',', $array);
            }
            
            return ! empty($order) ? ' ORDER BY '.$order : '';
        }
        
        /**
         * limit分析
         * @access protected
         *
         * @param mixed $limit
         *
         * @return string
         */
        protected function parseLimit($limit)
        {
            return ! empty($limit) ? ' LIMIT '.$limit.' ' : '';
        }
        
        /**
         * union分析
         * @access protected
         *
         * @param mixed $union
         *
         * @return string
         */
        protected function parseUnion($union)
        {
            if (empty($union)) {
                return '';
            }
            if (isset($union['_all'])) {
                $str = 'UNION ALL ';
                unset($union['_all']);
            } else {
                $str = 'UNION ';
            }
            foreach ($union as $u) {
                $sql[] = $str.(is_array($u) ? $this->buildSelectSql($u) : $u);
            }
            
            return implode(' ', $sql);
        }
        
        /**
         * 设置锁机制
         * @access protected
         *
         * @param bool $lock
         *
         * @return string
         */
        protected function parseLock($lock = false)
        {
            return $lock ? ' FOR UPDATE ' : '';
        }
        
        /**
         * index分析，可在操作链中指定需要强制使用的索引
         * @access protected
         *
         * @param mixed $index
         *
         * @return string
         */
        protected function parseForce($index)
        {
            if (empty($index)) {
                return '';
            }
            if (is_array($index)) {
                $index = implode(",", $index);
            }
            
            return sprintf(" FORCE INDEX ( %s ) ", $index);
        }
        
        /**
         * 更新记录
         * @access public
         *
         * @param mixed $data    数据
         * @param array $options 表达式
         *
         * @return false | int
         */
        public function update($data, $options)
        {
            $this->model = $options['model'];
            $this->parseBind(! empty($options['bind']) ? $options['bind'] : []);
            $table = $this->parseTable($options['table']);
            $sql   = 'UPDATE '.$table.$this->parseSet($data);
            // 多表更新支持JOIN操作
            if (strpos($table, ',')) {
                $sql .= $this->parseJoin(! empty($options['join']) ? $options['join'] : '');
            }
            $sql .= $this->parseWhere(! empty($options['where']) ? $options['where'] : '');
            if ( ! strpos($table, ',')) {
                //  单表更新支持order和lmit
                $sql .= $this->parseOrder(! empty($options['order']) ? $options['order'] : '')
                        .$this->parseLimit(! empty($options['limit']) ? $options['limit'] : '');
            }
            $sql .= $this->parseComment(! empty($options['comment']) ? $options['comment'] : '');
            
            return $this->execute($sql, ! empty($options['fetch_sql']));
        }
        
        /**
         * set分析
         * @access protected
         *
         * @param array $data
         *
         * @return string
         */
        protected function parseSet($data)
        {
            foreach ($data as $key => $val) {
                if (is_array($val) && 'exp' == $val[0]) {
                    $set[] = $this->parseKey($key).'='.$val[1];
                } elseif (is_null($val)) {
                    $set[] = $this->parseKey($key).'=NULL';
                } elseif (is_scalar($val)) {// 过滤非标量数据
                    if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
                        $set[] = $this->parseKey($key).'='.$this->escapeString($val);
                    } else {
                        $name  = count($this->bind);
                        $set[] = $this->parseKey($key).'=:'.$name;
                        $this->bindParam($name, $val);
                    }
                }
            }
            
            return ' SET '.implode(',', $set);
        }
        
        /**
         * 删除记录
         * @access public
         *
         * @param array $options 表达式
         *
         * @return false | int
         */
        public function delete($options = [])
        {
            $this->model = $options['model'];
            $this->parseBind(! empty($options['bind']) ? $options['bind'] : []);
            $table = $this->parseTable($options['table']);
            $sql   = 'DELETE FROM '.$table;
            // 多表删除支持USING和JOIN操作
            if (strpos($table, ',')) {
                if ( ! empty($options['using'])) {
                    $sql .= ' USING '.$this->parseTable($options['using']).' ';
                }
                $sql .= $this->parseJoin(! empty($options['join']) ? $options['join'] : '');
            }
            $sql .= $this->parseWhere(! empty($options['where']) ? $options['where'] : '');
            if ( ! strpos($table, ',')) {
                // 单表删除支持order和limit
                $sql .= $this->parseOrder(! empty($options['order']) ? $options['order'] : '')
                        .$this->parseLimit(! empty($options['limit']) ? $options['limit'] : '');
            }
            $sql .= $this->parseComment(! empty($options['comment']) ? $options['comment'] : '');
            
            return $this->execute($sql, ! empty($options['fetch_sql']));
        }
        
        /**
         * 查找记录
         * @access public
         *
         * @param array $options 表达式
         *
         * @return mixed
         */
        public function select($options = [])
        {
            $this->model = $options['model'];
            $this->parseBind(! empty($options['bind']) ? $options['bind'] : []);
            $sql = $this->buildSelectSql($options);
            
            return $this->query($sql, ! empty($options['fetch_sql']));
        }
        
        /**
         * 执行查询 返回数据集
         * @access public
         *
         * @param string $str      sql指令
         * @param bool   $fetchSql 不执行只是获取SQL
         *
         * @throws \PDOException
         * @return mixed
         */
        public function query($str, $fetchSql = false)
        {
            $this->initConnect();
            if ( ! $this->_linkID) {
                return false;
            }
            $this->queryStr = $str;
            if ( ! empty($this->bind)) {
                $that           = $this;
                $this->queryStr = strtr(
                    $this->queryStr,
                    array_map(
                        function ($val) use ($that) {
                            return '\''.$that->escapeString($val).'\'';
                        },
                        $this->bind
                    )
                );
            }
            if ($fetchSql) {
                return $this->queryStr;
            }
            //释放前次的查询结果
            if ( ! empty($this->PDOStatement)) {
                $this->free();
            }
            $this->queryTimes++;
            // 记录查询操作
//            N('db_query', 1);
            // 调试开始
            $this->debug(true);
            $this->PDOStatement = $this->_linkID->prepare($str);
            if (false === $this->PDOStatement) {
                $this->error();
                
                return false;
            }
            foreach ($this->bind as $key => $val) {
                if (is_array($val)) {
                    $this->PDOStatement->bindValue($key, $val[0], $val[1]);
                } else {
                    $this->PDOStatement->bindValue($key, $val);
                }
            }
            $this->bind = [];
            try {
                $result = $this->PDOStatement->execute();
                // 调试结束
                $this->debug(false);
                if (false === $result) {
                    $this->error();
                    
                    return false;
                } else {
                    return $this->getResult();
                }
            } catch (PDOException $e) {
                $this->error();
                
                return false;
            }
        }
        
        /**
         * 获得所有的查询数据
         * @access private
         * @return array
         */
        private function getResult()
        {
            //返回数据集
            $result        = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
            $this->numRows = count($result);
            
            return $result;
        }
        
        /**
         * 获取最近一次查询的sql语句
         *
         * @param string $model 模型名
         *
         * @access public
         * @return string
         */
        public function getLastSql($model = '')
        {
            return $model ? $this->modelSql[$model] : $this->queryStr;
        }
        
        /**
         * 获取最近插入的ID
         * @access public
         * @return string
         */
        public function getLastInsID()
        {
            return $this->lastInsID;
        }
        
        /**
         * 获取最近的错误信息
         * @access public
         * @return string
         */
        public function getError()
        {
            return $this->error;
        }
        
        /**
         * 设置当前操作模型
         * @access public
         *
         * @param string $model 模型名
         *
         * @return void
         */
        public function setModel($model)
        {
            $this->model = $model;
        }
        
        /**
         * 析构方法
         * @access public
         */
        public function __destruct()
        {
            // 释放查询
            if ($this->PDOStatement) {
                $this->free();
            }
            // 关闭连接
            $this->close();
        }
        
        /**
         * 关闭数据库
         * @access public
         */
        public function close()
        {
            $this->_linkID = null;
        }
    }
