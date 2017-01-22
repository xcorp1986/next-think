<?php
    
    namespace Think;
    
    /**
     * Model模型类
     * 实现了ORM和ActiveRecords模式
     * Class Model
     * @package Think
     * @method mixed count(string $count = '') 统计数量，参数是要统计的字段名（可选）
     * @method string sum(string $sum) 获取求和，参数是要统计的字段名（必须）
     * @method string min(string $min) 获取最小值，参数是要统计的字段名（必须）
     * @method string max(string $max) 获取最大值，参数是要统计的字段名（必须）
     * @method string avg(string $avg) 获取平均值，参数是要统计的字段名（必须）
     * @method $this strict(bool $strict) 用于数据入库的严格检测
     * @method $this order(string | array $order) 用于对结果排序
     * @method $this alias(string $alias) 用于给当前数据表定义别名
     * @method $this having(string $having) 用于对查询的having支持
     * @method $this group(string $group) 用于对查询的group支持
     * @method $this lock(bool $lock) 用于数据库的锁机制
     * @method $this distinct(bool $distinct) 用于查询的distinct支持
     * @method $this auto(array $auto) 用于数据自动完成
     * @method $this filter(string $filter) 写入数据过滤
     * @method $this validate(array $validate) 用于数据自动验证
     * @method $this result(string $result) 用于返回数据转换
     * @method $this token(bool $token) 用于令牌验证
     * @method $this index(string $index) 用于数据集的强制索引
     * @method $this force($force)
     * @property-read string $tableName 数据表名（不包含表前缀）
     */
    class Model
    {
        /**
         * @const MODEL_INSERT 插入模型数据
         */
        const MODEL_INSERT = 1;
        /**
         * @const MODEL_UPDATE 更新模型数据
         */
        const MODEL_UPDATE = 2;
        /**
         * @const MODEL_BOTH 包含上面两种方式
         */
        const MODEL_BOTH = 3;
        /**
         * @const MUST_VALIDATE 必须验证
         */
        const MUST_VALIDATE = 1;
        /**
         * @const EXISTS_VALIDATE 表单存在字段则验证
         */
        const EXISTS_VALIDATE = 0;
        /**
         * @const VALUE_VALIDATE 表单值不为空则验证
         */
        const VALUE_VALIDATE = 2;
        
        /**
         * @var \Think\Db\Driver $db 当前数据库操作对象
         */
        protected $db = null;
        /**
         * @var string $pk 主键名称
         */
        protected $pk = 'id';
        /**
         * @var bool $autoinc 主键是否自动增长
         */
        protected $autoinc = false;
        /**
         * @var mixed|null $tablePrefix 数据表前缀
         */
        protected $tablePrefix = null;
        /**
         * @var string $name 模型名称
         */
        protected $name = '';
        /**
         * @var string $dbName 数据库名称
         */
        protected $dbName = '';
        /**
         * @var string $connection 数据库配置
         */
        protected $connection = '';
        /**
         * @var string $tableName 数据表名（不包含表前缀）
         */
        protected $tableName = '';
        /**
         * @var string $trueTableName 实际数据表名（包含表前缀）
         */
        protected $trueTableName = '';
        /**
         * @var string $error 最近错误信息
         */
        protected $error = '';
        /**
         * @var array $fields 字段信息
         */
        protected $fields = [];
        /**
         * @var array $data 数据信息
         */
        protected $data = [];
        /**
         * @var array $options 查询表达式参数
         */
        protected $options = [];
        /**
         * @var array $_validate 自动验证定义
         */
        protected $_validate = [];
        /**
         * @var array $_auto 自动完成定义
         */
        protected $_auto = [];
        /**
         * @var array $_map 字段映射定义
         */
        protected $_map = [];
        /**
         * @var array $_scope 命名范围定义
         */
        protected $_scope = [];
        /**
         * @var bool $autoCheckFields 是否自动检测数据表字段信息
         */
        protected $autoCheckFields = true;
        /**
         * @var bool $patchValidate 是否批处理验证
         */
        protected $patchValidate = false;
        /**
         * @var array $methods 链操作方法列表
         */
        protected $methods = [
            'strict',
            'order',
            'alias',
            'having',
            'group',
            'lock',
            'distinct',
            'auto',
            'filter',
            'validate',
            'result',
            'token',
            'index',
            'force',
        ];
        /**
         * @var array $_db 数据库对象池
         */
        private $_db = [];
        
        /**
         * 取得DB类的实例对象
         * @access public
         * @param string $name 模型名称
         */
        public function __construct($name = '')
        {
            if (method_exists($this, '_init')) {
                $this->_init();
            }
            // 获取模型名称
            if (!empty($name)) {
                $this->name = $name;
            }
            // 设置表前缀
            if (!isset($this->tablePrefix)) {
                $this->tablePrefix = C('DB_PREFIX');
            }
            //连接数据库
            $this->connect();
        }
        
        /**
         * 附加方法
         */
        protected function _init()
        {
        }
        
        /**
         * 切换当前的数据库连接
         * @access public
         * @param mixed $linkNum 连接序号
         * @param array $config  数据库连接信息
         * @return $this
         */
        public function connect($linkNum = 0, array $config = [])
        {
            $this->_db[$linkNum] = Db::getInstance($config);
            // 切换数据库连接
            $this->db = $this->_db[$linkNum];
            $this->_after_db();
            // 字段检测
            if (!empty($this->name) && $this->autoCheckFields) {
                $this->_checkTableInfo();
            }
            
            return $this;
        }
        
        protected function _after_db()
        {
        }
        
        /**
         * 自动检测数据表信息
         * @access protected
         * @return void
         */
        protected function _checkTableInfo()
        {
            // 如果不是Model类 自动记录数据表信息
            // 只在第一次执行记录
            if (empty($this->fields)) {
                // 如果数据表字段没有定义则自动获取
                if (C('DB_FIELDS_CACHE')) {
                    $db = $this->dbName ?: C('DB_NAME');
                    $fields = F('_fields/' . strtolower($db . '.' . $this->tablePrefix . $this->name));
                    if ($fields) {
                        $this->fields = $fields;
                        if (!empty($fields['_pk'])) {
                            $this->pk = $fields['_pk'];
                        }
                        
                        return;
                    }
                }
                // 每次都会读取数据表信息
                $this->flush();
            }
        }
        
        /**
         * 获取字段信息并缓存
         * @access public
         * @return void
         */
        public function flush()
        {
            // 缓存不存在则查询数据表信息
            $this->db->setModel($this->name);
            $fields = $this->db->getFields($this->getTableName());
            // 无法获取字段信息
            if (!$fields) {
                return false;
            }
            $this->fields = array_keys($fields);
            unset($this->fields['_pk']);
            foreach ($fields as $key => $val) {
                // 记录字段类型
                $type[$key] = $val['type'];
                if ($val['primary']) {
                    // 增加复合主键支持
                    if (isset($this->fields['_pk']) && $this->fields['_pk'] != null) {
                        if (is_string($this->fields['_pk'])) {
                            $this->pk = [$this->fields['_pk']];
                            $this->fields['_pk'] = $this->pk;
                        }
                        $this->pk[] = $key;
                        $this->fields['_pk'][] = $key;
                    } else {
                        $this->pk = $key;
                        $this->fields['_pk'] = $key;
                    }
                    if ($val['autoinc']) {
                        $this->autoinc = true;
                    }
                }
            }
            // 记录字段类型信息
            $this->fields['_type'] = $type;
            
            // 2008-3-7 增加缓存开关控制
            if (C('DB_FIELDS_CACHE')) {
                // 永久缓存数据表信息
                $db = $this->dbName ?: C('DB_NAME');
                F('_fields/' . strtolower($db . '.' . $this->tablePrefix . $this->name), $this->fields);
            }
        }
        
        /**
         * 得到完整的数据表名
         * @access public
         * @return string
         */
        public function getTableName()
        {
            if (empty($this->trueTableName)) {
                $tableName = $this->tablePrefix ?: '';
                if (!empty($this->tableName)) {
                    $tableName .= $this->tableName;
                } else {
                    $tableName .= parse_name($this->name);
                }
                $this->trueTableName = strtolower($tableName);
            }
            
            return ($this->dbName ? $this->dbName . '.' : '') . $this->trueTableName;
        }
        
        /**
         * 得到当前的数据对象名称
         * @access public
         * @return string
         */
        public function getModelName()
        {
            if (empty($this->name)) {
                $name = substr(get_class($this), 0, -strlen(C('DEFAULT_M_LAYER')));
                if ($pos = strrpos($name, '\\')) {
                    //有命名空间
                    $this->name = substr($name, $pos + 1);
                } else {
                    $this->name = $name;
                }
            }
            
            return $this->name;
        }
        
        /**
         * 获取数据对象的值
         * @access public
         * @param string $name 名称
         * @return mixed
         */
        public function __get($name)
        {
            return isset($this->data[$name]) ? $this->data[$name] : null;
        }
        
        /**
         * 设置数据对象的值
         * @access public
         * @param string $name  名称
         * @param mixed  $value 值
         * @return void
         */
        public function __set($name, $value)
        {
            // 设置数据对象属性
            $this->data[$name] = $value;
        }
        
        // 写入数据前的回调方法 包括新增和更新
        
        /**
         * 检测数据对象的值
         * @access public
         * @param string $name 名称
         * @return bool
         */
        public function __isset($name)
        {
            return isset($this->data[$name]);
        }
        
        /**
         * 销毁数据对象的值
         * @access public
         * @param string $name 名称
         * @return void
         */
        public function __unset($name)
        {
            unset($this->data[$name]);
        }
        
        /**
         * 利用__call方法实现一些特殊的Model方法
         * @access public
         * @param string $method 方法名称
         * @param array  $args   调用参数
         * @return mixed
         */
        public function __call($method, $args)
        {
            if (in_array(strtolower($method), $this->methods, true)) {
                // 连贯操作的实现
                $this->options[strtolower($method)] = $args[0];
                
                return $this;
            } elseif (in_array(strtolower($method), ['count', 'sum', 'min', 'max', 'avg'], true)) {
                // 统计查询的实现
                $field = isset($args[0]) ? $args[0] : '*';
                
                return $this->getField(strtoupper($method) . '(' . $field . ') AS tp_' . $method);
            } elseif (strtolower(substr($method, 0, 5)) == 'getby') {
                // 根据某个字段获取记录
                $field = parse_name(substr($method, 5));
                $where[$field] = $args[0];
                
                return $this->where($where)->find();
            } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
                // 根据某个字段获取记录的某个值
                $name = parse_name(substr($method, 10));
                $where[$name] = $args[0];
                
                return $this->where($where)->getField($args[1]);
            } elseif (isset($this->_scope[$method])) {
                // 命名范围的单独调用支持
                return $this->scope($method, $args[0]);
            } else {
                E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
                
                return;
            }
        }
        
        /**
         * 获取一条记录的某个字段值
         * @access   public
         * @param string $field 字段名
         * @param null   $sepa  字段数据间隔符号 NULL返回数组
         * @return mixed
         */
        public function getField($field, $sepa = null)
        {
            $options['field'] = $field;
            $options = $this->_parseOptions($options);
            // 判断查询缓存
            if (isset($options['cache'])) {
                $cache = $options['cache'];
                $key = is_string($cache['key']) ? $cache['key'] : md5($sepa . serialize($options));
                $data = S($key, '', $cache);
                if (false !== $data) {
                    return $data;
                }
            }
            $field = trim($field);
            // 多字段
            if (strpos($field, ',') && false !== $sepa) {
                if (!isset($options['limit'])) {
                    $options['limit'] = is_numeric($sepa) ? $sepa : '';
                }
                $resultSet = $this->db->select($options);
                if (!empty($resultSet)) {
                    if (is_string($resultSet)) {
                        return $resultSet;
                    }
                    $_field = explode(',', $field);
                    $field = array_keys($resultSet[0]);
                    $key1 = array_shift($field);
                    $key2 = array_shift($field);
                    $cols = [];
                    $count = count($_field);
                    foreach ($resultSet as $result) {
                        $name = $result[$key1];
                        if (2 == $count) {
                            $cols[$name] = $result[$key2];
                        } else {
                            $cols[$name] = is_string($sepa) ? implode($sepa, array_slice($result, 1)) : $result;
                        }
                    }
                    if (isset($cache)) {
                        S($key, $cols, $cache);
                    }
                    
                    return $cols;
                }
                // 查找一条记录
            } else {
                // 返回数据个数
                if (true !== $sepa) {
                    // 当sepa指定为true的时候 返回所有数据
                    $options['limit'] = is_numeric($sepa) ? $sepa : 1;
                }
                $result = $this->db->select($options);
                if (!empty($result)) {
                    if (is_string($result)) {
                        return $result;
                    }
                    if (true !== $sepa && 1 == $options['limit']) {
                        $data = reset($result[0]);
                        if (isset($cache)) {
                            S($key, $data, $cache);
                        }
                        
                        return $data;
                    }
                    foreach ($result as $val) {
                        $array[] = $val[$field];
                    }
                    if (isset($cache)) {
                        S($key, $array, $cache);
                    }
                    
                    return $array;
                }
            }
            
            return null;
        }
        
        /**
         * 分析表达式
         * @access protected
         * @param array $options 表达式参数
         * @return array
         */
        protected function _parseOptions(array $options = [])
        {
            if (is_array($options)) {
                $options = array_merge($this->options, $options);
            }
            
            if (!isset($options['table'])) {
                // 自动获取表名
                $options['table'] = $this->getTableName();
                $fields = $this->fields;
            } else {
                // 指定数据表 则重新获取字段列表 但不支持类型检测
                $fields = $this->getDbFields();
            }
            
            // 数据表别名
            if (!empty($options['alias'])) {
                $options['table'] .= ' ' . $options['alias'];
            }
            // 记录操作的模型名称
            $options['model'] = $this->name;
            
            // 字段类型验证
            if (isset($options['where']) && is_array($options['where']) && !empty($fields) && !isset($options['join'])) {
                // 对数组查询条件进行字段类型检查
                foreach ($options['where'] as $key => $val) {
                    $key = trim($key);
                    if (in_array($key, $fields, true)) {
                        if (is_scalar($val)) {
                            $this->_parseType($options['where'], $key);
                        }
                    } elseif (!is_numeric($key) && '_' != substr($key, 0, 1) && false === strpos($key, '.') && false === strpos($key, '(') && false === strpos($key, '|') && false === strpos($key, '&')) {
                        if (!empty($this->options['strict'])) {
                            E(L('_ERROR_QUERY_EXPRESS_') . ':[' . $key . '=>' . $val . ']');
                        }
                        unset($options['where'][$key]);
                    }
                }
            }
            // 查询过后清空sql表达式组装 避免影响下次查询
            $this->options = [];
            // 表达式过滤
            $this->_options_filter($options);
            
            return $options;
        }
        
        /**
         * 获取数据表字段信息
         * @access public
         * @return array
         */
        public function getDbFields()
        {
            if (isset($this->options['table'])) {
                // 动态指定表名
                if (is_array($this->options['table'])) {
                    $table = key($this->options['table']);
                } else {
                    $table = $this->options['table'];
                    if (strpos($table, ')')) {
                        // 子查询
                        return false;
                    }
                }
                $fields = $this->db->getFields($table);
                
                return $fields ? array_keys($fields) : false;
            }
            if ($this->fields) {
                $fields = $this->fields;
                unset($fields['_type'], $fields['_pk']);
                
                return $fields;
            }
            
            return false;
        }
        
        /**
         * 数据类型检测
         * @access protected
         * @param mixed  $data 数据
         * @param string $key  字段名
         * @return void
         */
        protected function _parseType(&$data, $key)
        {
            if (!isset($this->options['bind'][':' . $key]) && isset($this->fields['_type'][$key])) {
                $fieldType = strtolower($this->fields['_type'][$key]);
                if (false !== strpos($fieldType, 'enum')) {
                    // 支持ENUM类型优先检测
                } elseif (false === strpos($fieldType, 'bigint') && false !== strpos($fieldType, 'int')) {
                    $data[$key] = intval($data[$key]);
                } elseif (false !== strpos($fieldType, 'float') || false !== strpos($fieldType, 'double')) {
                    $data[$key] = floatval($data[$key]);
                } elseif (false !== strpos($fieldType, 'bool')) {
                    $data[$key] = (bool)$data[$key];
                }
            }
        }
        
        /**
         * 表达式过滤回调方法
         * @param $options
         */
        protected function _options_filter(&$options)
        {
        }
        
        /**
         * 查询数据
         * @access public
         * @param mixed $options 表达式参数
         * @return mixed
         */
        public function find($options = null)
        {
            if (is_numeric($options) || is_string($options)) {
                $where[$this->getPk()] = $options;
                $options = [];
                $options['where'] = $where;
            }
            // 根据复合主键查找记录
            $pk = $this->getPk();
            if (is_array($options) && (count($options) > 0) && is_array($pk)) {
                // 根据复合主键查询
                $count = 0;
                foreach (array_keys($options) as $key) {
                    if (is_int($key)) {
                        $count++;
                    }
                }
                if ($count == count($pk)) {
                    $i = 0;
                    foreach ($pk as $field) {
                        $where[$field] = $options[$i];
                        unset($options[$i++]);
                    }
                    $options['where'] = $where;
                } else {
                    return false;
                }
            }
            // 总是查找一条记录
            $options['limit'] = 1;
            // 分析表达式
            $options = $this->_parseOptions($options);
            // 判断查询缓存
            if (isset($options['cache'])) {
                $cache = $options['cache'];
                $key = is_string($cache['key']) ? $cache['key'] : to_guid_string($options);
                $data = S($key, '', $cache);
                if (false !== $data) {
                    $this->data = $data;
                    
                    return $data;
                }
            }
            $resultSet = $this->db->select($options);
            if (false === $resultSet) {
                return false;
            }
            if (empty($resultSet)) {// 查询结果为空
                return null;
            }
            if (is_string($resultSet)) {
                return $resultSet;
            }
            
            // 读取数据后的处理
            $data = $this->_read_data($resultSet[0]);
            $this->_after_find($data, $options);
            if (!empty($this->options['result'])) {
                return $this->returnResult($data, $this->options['result']);
            }
            $this->data = $data;
            if (isset($cache)) {
                S($key, $data, $cache);
            }
            
            return $this->data;
        }
        
        /**
         * 获取主键名称
         * @access public
         * @return string
         */
        public function getPk()
        {
            return $this->pk;
        }
        
        /**
         * 数据读取后的处理
         * @todo   check
         * @access protected
         * @param array $data 当前数据
         * @return array
         */
        protected function _read_data($data)
        {
            // 检查字段映射
            if (!empty($this->_map) && C('READ_DATA_MAP')) {
                foreach ($this->_map as $key => $val) {
                    if (isset($data[$val])) {
                        $data[$key] = $data[$val];
                        unset($data[$val]);
                    }
                }
            }
            
            return $data;
        }
        
        /**
         * 查询成功的回调方法
         * @param $result
         * @param $options
         */
        protected function _after_find(&$result, $options)
        {
        }
        
        /**
         * 返回数据类型
         * @param        $data
         * @param string $type
         * @return mixed|string
         */
        protected function returnResult($data, $type = '')
        {
            if ($type) {
                if (is_callable($type)) {
                    return call_user_func($type, $data);
                }
                switch (strtolower($type)) {
                    case 'json':
                        return json_encode($data);
                    case 'xml':
                        return xml_encode($data);
                }
            }
            
            return $data;
        }
        
        /**
         * 指定查询条件 支持安全过滤
         * @access public
         * @param array $where 条件表达式
         * @return $this
         */
        public function where(array $where = [])
        {
            if (isset($this->options['where'])) {
                $this->options['where'] = array_merge($this->options['where'], $where);
            } else {
                $this->options['where'] = $where;
            }
            
            return $this;
        }
        
        /**
         * 调用命名范围
         * @access public
         * @param mixed $scope 命名范围名称 支持多个 和直接定义
         * @param array $args  参数
         * @return $this
         */
        public function scope($scope = '', $args = null)
        {
            if ('' === $scope) {
                if (isset($this->_scope['default'])) {
                    // 默认的命名范围
                    $options = $this->_scope['default'];
                } else {
                    return $this;
                }
            } elseif (is_string($scope)) {
                // 支持多个命名范围调用 用逗号分割
                $scopes = explode(',', $scope);
                $options = [];
                foreach ($scopes as $name) {
                    if (!isset($this->_scope[$name])) {
                        continue;
                    }
                    $options = array_merge($options, $this->_scope[$name]);
                }
                if (!empty($args) && is_array($args)) {
                    $options = array_merge($options, $args);
                }
            } elseif (is_array($scope)) {
                // 直接传入命名范围定义
                $options = $scope;
            }
            
            if (is_array($options) && !empty($options)) {
                $this->options = array_merge($this->options, array_change_key_case($options));
            }
            
            return $this;
        }
        
        /**
         * 新增数据
         * @access public
         * @param mixed   $data    数据
         * @param array   $options 表达式
         * @param boolean $replace 是否replace
         * @return mixed
         */
        public function add($data = '', $options = [], $replace = false)
        {
            if (empty($data)) {
                // 没有传递数据，获取当前数据对象的值
                if (!empty($this->data)) {
                    $data = $this->data;
                    // 重置数据
                    $this->data = [];
                } else {
                    $this->error = L('_DATA_TYPE_INVALID_');
                    
                    return false;
                }
            }
            // 数据处理
            $data = $this->_facade($data);
            // 分析表达式
            $options = $this->_parseOptions($options);
            if (false === $this->_before_insert($data, $options)) {
                return false;
            }
            // 写入数据到数据库
            $result = $this->db->insert($data, $options, $replace);
            if (false !== $result && is_numeric($result)) {
                $pk = $this->getPk();
                // 增加复合主键支持
                if (is_array($pk)) {
                    return $result;
                }
                $insertId = $this->getLastInsID();
                if ($insertId) {
                    // 自增主键返回插入ID
                    $data[$pk] = $insertId;
                    if (false === $this->_after_insert($data, $options)) {
                        return false;
                    }
                    
                    return $insertId;
                }
                if (false === $this->_after_insert($data, $options)) {
                    return false;
                }
            }
            
            return $result;
        }
        
        /**
         * 对保存到数据库的数据进行处理
         * @access protected
         * @param mixed $data 要操作的数据
         * @return bool
         */
        protected function _facade($data)
        {
            
            // 检查数据字段合法性
            if (!empty($this->fields)) {
                if (!empty($this->options['field'])) {
                    $fields = $this->options['field'];
                    unset($this->options['field']);
                    if (is_string($fields)) {
                        $fields = explode(',', $fields);
                    }
                } else {
                    $fields = $this->fields;
                }
                foreach ($data as $key => $val) {
                    if (!in_array($key, $fields, true)) {
                        if (!empty($this->options['strict'])) {
                            E(L('_DATA_TYPE_INVALID_') . ':[' . $key . '=>' . $val . ']');
                        }
                        unset($data[$key]);
                    } elseif (is_scalar($val)) {
                        // 字段类型检查 和 强制转换
                        $this->_parseType($data, $key);
                    }
                }
            }
            
            // 安全过滤
            if (!empty($this->options['filter'])) {
                $data = array_map($this->options['filter'], $data);
                unset($this->options['filter']);
            }
            $this->_before_write($data);
            
            return $data;
        }
        
        protected function _before_write(&$data)
        {
        }
        
        /**
         * 插入数据前的回调方法
         * @param $data
         * @param $options
         */
        protected function _before_insert(&$data, $options)
        {
        }
        
        /**
         * 返回最后插入的ID
         * @access public
         * @return string
         */
        public function getLastInsID()
        {
            return $this->db->getLastInsID();
        }
        
        /**
         * 插入成功后的回调方法
         * @param $data
         * @param $options
         */
        protected function _after_insert($data, $options)
        {
        }
        
        public function addAll(array $dataList = [], array $options = [], $replace = false)
        {
            if (empty($dataList)) {
                $this->error = L('_DATA_TYPE_INVALID_');
                
                return false;
            }
            // 数据处理
            foreach ($dataList as $key => $data) {
                $dataList[$key] = $this->_facade($data);
            }
            // 分析表达式
            $options = $this->_parseOptions($options);
            // 写入数据到数据库
            $result = $this->db->insertAll($dataList, $options, $replace);
            if (false !== $result) {
                $insertId = $this->getLastInsID();
                if ($insertId) {
                    return $insertId;
                }
            }
            
            return $result;
        }
        
        /**
         * 通过Select方式添加记录
         * @access public
         * @param string $fields  要插入的数据表字段名
         * @param string $table   要插入的数据表名
         * @param array  $options 表达式
         * @return bool
         */
        public function selectAdd($fields = '', $table = '', $options = [])
        {
            // 分析表达式
            $options = $this->_parseOptions($options);
            // 写入数据到数据库
            if (false === $result = $this->db->selectInsert($fields ?: $options['field'], $table ?: $this->getTableName(), $options)) {
                // 数据库插入操作失败
                $this->error = L('_OPERATION_WRONG_');
                
                return false;
            } else {
                // 插入成功
                return $result;
            }
        }
        
        /**
         * 删除数据
         * @access public
         * @param mixed $options 表达式
         * @return mixed
         */
        public function delete($options = [])
        {
            $pk = $this->getPk();
            if (empty($options) && empty($this->options['where'])) {
                // 如果删除条件为空 则删除当前数据对象所对应的记录
                if (!empty($this->data) && isset($this->data[$pk])) {
                    return $this->delete($this->data[$pk]);
                } else {
                    return false;
                }
            }
            if (is_numeric($options) || is_string($options)) {
                // 根据主键删除记录
                if (strpos($options, ',')) {
                    $where[$pk] = ['IN', $options];
                } else {
                    $where[$pk] = $options;
                }
                $options = [];
                $options['where'] = $where;
            }
            // 根据复合主键删除记录
            if (is_array($options) && (count($options) > 0) && is_array($pk)) {
                $count = 0;
                foreach (array_keys($options) as $key) {
                    if (is_int($key)) {
                        $count++;
                    }
                }
                if ($count == count($pk)) {
                    $i = 0;
                    foreach ($pk as $field) {
                        $where[$field] = $options[$i];
                        unset($options[$i++]);
                    }
                    $options['where'] = $where;
                } else {
                    return false;
                }
            }
            // 分析表达式
            $options = $this->_parseOptions($options);
            if (empty($options['where'])) {
                // 如果条件为空 不进行删除操作 除非设置 1=1
                return false;
            }
            if (is_array($options['where']) && isset($options['where'][$pk])) {
                $pkValue = $options['where'][$pk];
            }
            
            if (false === $this->_before_delete($options)) {
                return false;
            }
            $result = $this->db->delete($options);
            if (false !== $result && is_numeric($result)) {
                $data = [];
                if (isset($pkValue)) {
                    $data[$pk] = $pkValue;
                }
                $this->_after_delete($data, $options);
            }
            
            // 返回删除记录个数
            return $result;
        }
        
        /**
         * 删除数据前的回调方法
         * @param $options
         */
        protected function _before_delete($options)
        {
        }
        
        /**
         * 删除成功后的回调方法
         * @param $data
         * @param $options
         */
        protected function _after_delete($data, $options)
        {
        }
        
        /**
         * 生成查询SQL 可用于子查询
         * @access public
         * @return string
         */
        public function buildSql()
        {
            return '( ' . $this->fetchSql(true)->select() . ' )';
        }
        
        /**
         * 查询数据集
         * @access public
         * @param array $options 表达式参数
         * @return mixed
         */
        public function select($options = [])
        {
            $pk = $this->getPk();
            if (is_string($options) || is_numeric($options)) {
                // 根据主键查询
                if (strpos($options, ',')) {
                    $where[$pk] = ['IN', $options];
                } else {
                    $where[$pk] = $options;
                }
                $options = [];
                $options['where'] = $where;
            } elseif (is_array($options) && (count($options) > 0) && is_array($pk)) {
                // 根据复合主键查询
                $count = 0;
                foreach (array_keys($options) as $key) {
                    if (is_int($key)) {
                        $count++;
                    }
                }
                if ($count == count($pk)) {
                    $i = 0;
                    foreach ($pk as $field) {
                        $where[$field] = $options[$i];
                        unset($options[$i++]);
                    }
                    $options['where'] = $where;
                } else {
                    return false;
                }
            } elseif (false === $options) {
                // 用于子查询 不查询只返回SQL
                $options['fetch_sql'] = true;
            }
            // 分析表达式
            $options = $this->_parseOptions($options);
            // 判断查询缓存
            if (isset($options['cache'])) {
                $cache = $options['cache'];
                $key = is_string($cache['key']) ? $cache['key'] : to_guid_string($options);
                $data = S($key, '', $cache);
                if (false !== $data) {
                    return $data;
                }
            }
            $resultSet = $this->db->select($options);
            if (false === $resultSet) {
                return false;
            }
            if (!empty($resultSet)) {
                // 有查询结果
                if (is_string($resultSet)) {
                    return $resultSet;
                }
                
                $resultSet = array_map([$this, '_read_data'], $resultSet);
                $this->_after_select($resultSet, $options);
                if (isset($options['index'])) {
                    // 对数据集进行索引
                    $index = explode(',', $options['index']);
                    foreach ($resultSet as $result) {
                        $_key = $result[$index[0]];
                        if (isset($index[1]) && isset($result[$index[1]])) {
                            $cols[$_key] = $result[$index[1]];
                        } else {
                            $cols[$_key] = $result;
                        }
                    }
                    $resultSet = $cols;
                }
            }
            
            if (isset($cache)) {
                S($key, $resultSet, $cache);
            }
            
            return $resultSet;
        }
        
        /**
         * 查询成功后的回调方法
         * @param $resultSet
         * @param $options
         */
        protected function _after_select(&$resultSet, $options)
        {
        }
        
        /**
         * 获取执行的SQL语句
         * @access public
         * @param boolean $fetch 是否返回sql
         * @return $this
         */
        public function fetchSql($fetch = true)
        {
            $this->options['fetch_sql'] = $fetch;
            
            return $this;
        }
        
        /**
         * 字段值增长
         * @access public
         * @param string $field    字段名
         * @param int    $step     增长值
         * @param int    $lazyTime 延时时间(s)
         * @return bool
         */
        public function setInc($field, $step = 1, $lazyTime = 0)
        {
            // 延迟写入
            if ($lazyTime > 0) {
                $condition = $this->options['where'];
                $guid = md5($this->name . '_' . $field . '_' . serialize($condition));
                $step = $this->lazyWrite($guid, $step, $lazyTime);
                if (empty($step)) {
                    // 等待下次写入
                    return true;
                } elseif ($step < 0) {
                    $step = '-' . $step;
                }
            }
            
            return $this->setField($field, ['exp', '`' . $field . '`+' . $step]);
        }
        
        /**
         * 延时更新检查 返回false表示需要延时
         * 否则返回实际写入的数值
         * @access public
         * @param string $guid     写入标识
         * @param int    $step     写入步进值
         * @param int    $lazyTime 延时时间(s)
         * @return false|integer
         */
        protected function lazyWrite($guid, $step, $lazyTime)
        {
            // 存在缓存写入数据
            if (false !== ($value = S($guid))) {
                if (NOW_TIME > S($guid . '_time') + $lazyTime) {
                    // 延时更新时间到了，删除缓存数据 并实际写入数据库
                    S($guid, null);
                    S($guid . '_time', null);
                    
                    return $value + $step;
                } else {
                    // 追加数据到缓存
                    S($guid, $value + $step);
                    
                    return false;
                }
            } else {
                // 没有缓存数据
                S($guid, $step);
                // 计时开始
                S($guid . '_time', NOW_TIME);
                
                return false;
            }
        }
        
        /**
         * 设置记录的某个字段值
         * 支持使用数据库字段和方法
         * @access public
         * @param string|array $field 字段名
         * @param string       $value 字段值
         * @return bool
         */
        public function setField($field, $value = '')
        {
            if (is_array($field)) {
                $data = $field;
            } else {
                $data[$field] = $value;
            }
            
            return $this->save($data);
        }
        
        /**
         * 保存数据
         * @access public
         * @param mixed $data    数据
         * @param array $options 表达式
         * @return bool
         */
        public function save($data = '', $options = [])
        {
            if (empty($data)) {
                // 没有传递数据，获取当前数据对象的值
                if (!empty($this->data)) {
                    $data = $this->data;
                    // 重置数据
                    $this->data = [];
                } else {
                    $this->error = L('_DATA_TYPE_INVALID_');
                    
                    return false;
                }
            }
            // 数据处理
            $data = $this->_facade($data);
            if (empty($data)) {
                // 没有数据则不执行
                $this->error = L('_DATA_TYPE_INVALID_');
                
                return false;
            }
            // 分析表达式
            $options = $this->_parseOptions($options);
            $pk = $this->getPk();
            if (!isset($options['where'])) {
                // 如果存在主键数据 则自动作为更新条件
                if (is_string($pk) && isset($data[$pk])) {
                    $where[$pk] = $data[$pk];
                    unset($data[$pk]);
                } elseif (is_array($pk)) {
                    // 增加复合主键支持
                    foreach ($pk as $field) {
                        if (isset($data[$field])) {
                            $where[$field] = $data[$field];
                        } else {
                            // 如果缺少复合主键数据则不执行
                            $this->error = L('_OPERATION_WRONG_');
                            
                            return false;
                        }
                        unset($data[$field]);
                    }
                }
                if (!isset($where)) {
                    // 如果没有任何更新条件则不执行
                    $this->error = L('_OPERATION_WRONG_');
                    
                    return false;
                } else {
                    $options['where'] = $where;
                }
            }
            
            if (is_array($options['where']) && isset($options['where'][$pk])) {
                $pkValue = $options['where'][$pk];
            }
            if (false === $this->_before_update($data, $options)) {
                return false;
            }
            $result = $this->db->update($data, $options);
            if (false !== $result && is_numeric($result)) {
                if (isset($pkValue)) {
                    $data[$pk] = $pkValue;
                }
                $this->_after_update($data, $options);
            }
            
            return $result;
        }
        
        /**
         * 更新数据前的回调方法
         * @param $data
         * @param $options
         */
        protected function _before_update(&$data, $options)
        {
        }
        
        /**
         * 更新成功后的回调方法
         * @param $data
         * @param $options
         */
        protected function _after_update($data, $options)
        {
        }
        
        /**
         * 字段值减少
         * @access public
         * @param string $field    字段名
         * @param int    $step     减少值
         * @param int    $lazyTime 延时时间(s)
         * @return bool
         */
        public function setDec($field, $step = 1, $lazyTime = 0)
        {
            // 延迟写入
            if ($lazyTime > 0) {
                $condition = $this->options['where'];
                $guid = md5($this->name . '_' . $field . '_' . serialize($condition));
                $step = $this->lazyWrite($guid, -$step, $lazyTime);
                if (empty($step)) {
                    // 等待下次写入
                    return true;
                } elseif ($step > 0) {
                    $step = '-' . $step;
                }
            }
            
            return $this->setField($field, ['exp', '`' . $field . '`-' . $step]);
        }
        
        /**
         * 创建数据对象 但不保存到数据库
         * @access public
         * @param mixed  $data 创建数据
         * @param string $type 状态
         * @return mixed
         */
        public function create($data = '', $type = '')
        {
            // 如果没有传值默认取POST数据
            if (empty($data)) {
                $data = I('post.');
            } elseif (is_object($data)) {
                $data = get_object_vars($data);
            }
            // 验证数据
            if (empty($data) || !is_array($data)) {
                $this->error = L('_DATA_TYPE_INVALID_');
                
                return false;
            }
            
            // 状态
            $type = $type ?: (!empty($data[$this->getPk()]) ? self::MODEL_UPDATE : self::MODEL_INSERT);
            
            // 检查字段映射
            $data = $this->parseFieldsMap($data, 0);
            
            // 检测提交字段的合法性
            if (isset($this->options['field'])) {
                // $this->field('field1,field2...')->create()
                $fields = $this->options['field'];
                unset($this->options['field']);
            } elseif ($type == self::MODEL_INSERT && isset($this->insertFields)) {
                $fields = $this->insertFields;
            } elseif ($type == self::MODEL_UPDATE && isset($this->updateFields)) {
                $fields = $this->updateFields;
            }
            if (isset($fields)) {
                if (is_string($fields)) {
                    $fields = explode(',', $fields);
                }
                // 判断令牌验证字段
                if (C('TOKEN_ON')) {
                    $fields[] = C('TOKEN_NAME', null, '__hash__');
                }
                foreach ($data as $key => $val) {
                    if (!in_array($key, $fields)) {
                        unset($data[$key]);
                    }
                }
            }
            
            // 数据自动验证
            if (!$this->autoValidation($data, $type)) {
                return false;
            }
            
            // 表单令牌验证
            if (!$this->autoCheckToken($data)) {
                $this->error = L('_TOKEN_ERROR_');
                
                return false;
            }
            
            // 验证完成生成数据对象
            if ($this->autoCheckFields) {
                // 开启字段检测 则过滤非法字段数据
                $fields = $this->getDbFields();
                foreach ($data as $key => $val) {
                    if (!in_array($key, $fields)) {
                        unset($data[$key]);
                    }
                }
            }
            
            // 创建完成对数据进行自动处理
            $this->autoOperation($data, $type);
            // 赋值当前数据对象
            $this->data = $data;
            
            // 返回创建的数据以供其他调用
            return $data;
        }
        
        /**
         * 处理字段映射
         * @access public
         * @param array $data 当前数据
         * @param int   $type 类型 0 写入 1 读取
         * @return array
         */
        public function parseFieldsMap($data, $type = 1)
        {
            // 检查字段映射
            if (!empty($this->_map)) {
                foreach ($this->_map as $key => $val) {
                    if ($type == 1) { // 读取
                        if (isset($data[$val])) {
                            $data[$key] = $data[$val];
                            unset($data[$val]);
                        }
                    } else {
                        if (isset($data[$key])) {
                            $data[$val] = $data[$key];
                            unset($data[$key]);
                        }
                    }
                }
            }
            
            return $data;
        }
        
        /**
         * 自动表单验证
         * @access protected
         * @param array  $data 创建数据
         * @param string $type 创建类型
         * @return bool
         */
        protected function autoValidation($data, $type)
        {
            if (false === $this->options['validate']) {
                // 关闭自动验证
                return true;
            }
            if (!empty($this->options['validate'])) {
                $_validate = $this->options['validate'];
                unset($this->options['validate']);
            } elseif (!empty($this->_validate)) {
                $_validate = $this->_validate;
            }
            // 属性验证
            // 如果设置了数据自动验证则进行数据验证
            if (isset($_validate)) {
                if ($this->patchValidate) {
                    // 重置验证错误信息
                    $this->error = [];
                }
                foreach ($_validate as $val) {
                    // 验证因子定义格式
                    // array(field,rule,message,condition,type,when,params)
                    // 判断是否需要执行验证
                    if (empty($val[5]) || ($val[5] == self::MODEL_BOTH && $type < 3) || $val[5] == $type) {
                        if (0 == strpos($val[2], '{%') && strpos($val[2], '}')) {
                            // 支持提示信息的多语言 使用 {%语言定义} 方式
                            $val[2] = L(substr($val[2], 2, -1));
                        }
                        $val[3] = isset($val[3]) ? $val[3] : self::EXISTS_VALIDATE;
                        $val[4] = isset($val[4]) ? $val[4] : 'regex';
                        // 判断验证条件
                        switch ($val[3]) {
                            // 必须验证 不管表单是否有设置该字段
                            case self::MUST_VALIDATE:
                                if (false === $this->_validationField($data, $val)) {
                                    return false;
                                }
                                break;
                            // 值不为空的时候才验证
                            case self::VALUE_VALIDATE:
                                if ('' != trim($data[$val[0]])) {
                                    if (false === $this->_validationField($data, $val)) {
                                        return false;
                                    }
                                }
                                break;
                            // 默认表单存在该字段就验证
                            default:
                                if (isset($data[$val[0]])) {
                                    if (false === $this->_validationField($data, $val)) {
                                        return false;
                                    }
                                }
                        }
                    }
                }
                // 批量验证的时候最后返回错误
                if (!empty($this->error)) {
                    return false;
                }
            }
            
            return true;
        }
        
        /**
         * 验证表单字段 支持批量验证
         * 如果批量验证返回错误的数组信息
         * @access protected
         * @param array $data 创建数据
         * @param array $val  验证因子
         * @return bool
         */
        protected function _validationField($data, $val)
        {
            //当前字段已经有规则验证没有通过
            if ($this->patchValidate && isset($this->error[$val[0]])) {
                return;
            }
            if (false === $this->_validationFieldItem($data, $val)) {
                if ($this->patchValidate) {
                    $this->error[$val[0]] = $val[2];
                } else {
                    $this->error = $val[2];
                    
                    return false;
                }
            }
            
            return;
        }
        
        // 数据库切换后回调方法
        
        /**
         * 根据验证因子验证字段
         * @access protected
         * @param array $data 创建数据
         * @param array $val  验证因子
         * @return bool
         */
        protected function _validationFieldItem($data, $val)
        {
            switch (strtolower(trim($val[4]))) {
                // 使用函数进行验证
                case 'function':
                    // 调用方法进行验证
                case 'callback':
                    $args = isset($val[6]) ? (array)$val[6] : [];
                    if (is_string($val[0]) && strpos($val[0], ',')) {
                        $val[0] = explode(',', $val[0]);
                    }
                    if (is_array($val[0])) {
                        // 支持多个字段验证
                        foreach ($val[0] as $field) {
                            $_data[$field] = $data[$field];
                        }
                        array_unshift($args, $_data);
                    } else {
                        array_unshift($args, $data[$val[0]]);
                    }
                    if ('function' == $val[4]) {
                        return call_user_func_array($val[1], $args);
                    } else {
                        return call_user_func_array([&$this, $val[1]], $args);
                    }
                // 验证两个字段是否相同
                case 'confirm':
                    return $data[$val[0]] == $data[$val[1]];
                // 验证某个值是否唯一
                case 'unique':
                    if (is_string($val[0]) && strpos($val[0], ',')) {
                        $val[0] = explode(',', $val[0]);
                    }
                    $map = [];
                    if (is_array($val[0])) {
                        // 支持多个字段验证
                        foreach ($val[0] as $field) {
                            $map[$field] = $data[$field];
                        }
                    } else {
                        $map[$val[0]] = $data[$val[0]];
                    }
                    $pk = $this->getPk();
                    if (!empty($data[$pk]) && is_string($pk)) {
                        // 完善编辑的时候验证唯一
                        $map[$pk] = ['neq', $data[$pk]];
                    }
                    if ($this->where($map)->find()) {
                        return false;
                    }
                    
                    return true;
                // 检查附加规则
                default:
                    return $this->check($data[$val[0]], $val[1], $val[4]);
            }
        }
        
        /**
         * 验证数据 支持 in between equal length regex expire ip_allow ip_deny
         * @access public
         * @param string $value 验证数据
         * @param mixed  $rule  验证表达式
         * @param string $type  验证方式 默认为正则验证
         * @return bool
         */
        public function check($value, $rule, $type = 'regex')
        {
            $type = strtolower(trim($type));
            switch ($type) {
                // 验证是否在某个指定范围之内 逗号分隔字符串或者数组
                case 'in':
                case 'notin':
                    $range = is_array($rule) ? $rule : explode(',', $rule);
                    
                    return $type == 'in' ? in_array($value, $range) : !in_array($value, $range);
                // 验证是否在某个范围
                case 'between':
                    // 验证是否不在某个范围
                case 'notbetween':
                    if (is_array($rule)) {
                        $min = $rule[0];
                        $max = $rule[1];
                    } else {
                        list($min, $max) = explode(',', $rule);
                    }
                    
                    return $type == 'between' ? $value >= $min && $value <= $max : $value < $min || $value > $max;
                // 验证是否等于某个值
                case 'equal':
                    // 验证是否等于某个值
                case 'notequal':
                    return $type == 'equal' ? $value == $rule : $value != $rule;
                // 验证长度
                case 'length':
                    // 当前数据长度
                    $length = mb_strlen($value, 'utf-8');
                    if (strpos($rule, ',')) {
                        // 长度区间
                        list($min, $max) = explode(',', $rule);
                        
                        return $length >= $min && $length <= $max;
                    } else {
                        // 指定长度
                        return $length == $rule;
                    }
                //验证有效期
                case 'expire':
                    list($start, $end) = explode(',', $rule);
                    if (!is_numeric($start)) {
                        $start = strtotime($start);
                    }
                    if (!is_numeric($end)) {
                        $end = strtotime($end);
                    }
                    
                    return NOW_TIME >= $start && NOW_TIME <= $end;
                // IP 操作许可验证
                case 'ip_allow':
                    return in_array(get_client_ip(), explode(',', $rule));
                // IP 操作禁止验证
                case 'ip_deny':
                    return !in_array(get_client_ip(), explode(',', $rule));
                // 默认使用正则验证 可以使用验证类中定义的验证名称
                case 'regex':
                default:
                    // 检查附加规则
                    return $this->regex($value, $rule);
            }
        }
        
        /**
         * 使用正则验证数据
         * @access public
         * @param string $value 要验证的数据
         * @param string $rule  验证规则
         * @return bool
         */
        public function regex($value, $rule)
        {
            $validate = [
                'require'  => '/\S+/',
                'email'    => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
                'url'      => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
                'currency' => '/^\d+(\.\d+)?$/',
                'number'   => '/^\d+$/',
                'zip'      => '/^\d{6}$/',
                'integer'  => '/^[-\+]?\d+$/',
                'double'   => '/^[-\+]?\d+(\.\d+)?$/',
                'english'  => '/^[A-Za-z]+$/',
            ];
            // 检查是否有内置的正则表达式
            if (isset($validate[strtolower($rule)])) {
                $rule = $validate[strtolower($rule)];
            }
            
            return preg_match($rule, $value) === 1;
        }
        
        /**
         * 自动表单令牌验证
         * @todo ajax无刷新多次提交暂不能满足
         * @param $data
         * @return bool
         */
        public function autoCheckToken($data)
        {
            // 支持使用token(false) 关闭令牌验证
            if (isset($this->options['token']) && !$this->options['token']) {
                return true;
            }
            if (C('TOKEN_ON')) {
                $name = C('TOKEN_NAME', null, '__hash__');
                // 令牌数据无效
                if (!isset($data[$name]) || !isset($_SESSION[$name])) {
                    return false;
                }
                
                // 令牌验证
                list($key, $value) = explode('_', $data[$name]);
                // 防止重复提交
                if (isset($_SESSION[$name][$key]) && $value && $_SESSION[$name][$key] === $value) {
                    // 验证完成销毁session
                    unset($_SESSION[$name][$key]);
                    
                    return true;
                }
                // 开启TOKEN重置
                if (C('TOKEN_RESET')) {
                    unset($_SESSION[$name][$key]);
                }
                
                return false;
            }
            
            return true;
        }
        
        /**
         * 自动表单处理
         * @access public
         * @param array  $data 创建数据
         * @param string $type 创建类型
         * @return mixed
         */
        private function autoOperation(&$data, $type)
        {
            if (false === $this->options['auto']) {
                // 关闭自动完成
                return $data;
            }
            if (!empty($this->options['auto'])) {
                $_auto = $this->options['auto'];
                unset($this->options['auto']);
            } elseif (!empty($this->_auto)) {
                $_auto = $this->_auto;
            }
            // 自动填充
            if (isset($_auto)) {
                foreach ($_auto as $auto) {
                    // 填充因子定义格式
                    // array('field','填充内容','填充条件','附加规则',[额外参数])
                    // 默认为新增的时候自动填充
                    if (empty($auto[2])) {
                        $auto[2] = self::MODEL_INSERT;
                    }
                    if ($type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                        if (empty($auto[3])) {
                            $auto[3] = 'string';
                        }
                        switch (trim($auto[3])) {
                            //  使用函数进行填充 字段的值作为参数
                            case 'function':
                                // 使用回调方法
                            case 'callback':
                                $args = isset($auto[4]) ? (array)$auto[4] : [];
                                if (isset($data[$auto[0]])) {
                                    array_unshift($args, $data[$auto[0]]);
                                }
                                if ('function' == $auto[3]) {
                                    $data[$auto[0]] = call_user_func_array($auto[1], $args);
                                } else {
                                    $data[$auto[0]] = call_user_func_array([&$this, $auto[1]], $args);
                                }
                                break;
                            // 用其它字段的值进行填充
                            case 'field':
                                $data[$auto[0]] = $data[$auto[1]];
                                break;
                            // 为空忽略
                            case 'ignore':
                                if ($auto[1] === $data[$auto[0]]) {
                                    unset($data[$auto[0]]);
                                }
                                break;
                            // 默认作为字符串填充
                            case 'string':
                            default:
                                $data[$auto[0]] = $auto[1];
                        }
                        if (isset($data[$auto[0]]) && false === $data[$auto[0]]) {
                            unset($data[$auto[0]]);
                        }
                    }
                }
            }
            
            return $data;
        }
        
        /**
         * 存储过程返回多数据集
         * @todo   check
         * @access public
         * @param string $sql   SQL指令
         * @param mixed  $parse 是否需要解析SQL
         * @return array
         */
        public function procedure($sql, $parse = false)
        {
            return $this->db->procedure($sql, $parse);
        }
        
        /**
         * SQL查询
         * @access public
         * @param string $sql SQL指令
         * @return mixed
         */
        public function query($sql)
        {
            $sql = $this->parseSql($sql);
            
            return $this->db->query($sql);
        }
        
        /**
         * 解析SQL语句
         * @access public
         * @param string $sql SQL指令
         * @return string
         */
        protected function parseSql($sql)
        {
            $sql = strtr($sql, ['__TABLE__' => $this->getTableName(), '__PREFIX__' => $this->tablePrefix]);
            $prefix = $this->tablePrefix;
            $sql = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $sql);
            
            $this->db->setModel($this->name);
            
            return $sql;
        }
        
        /**
         * 执行SQL语句
         * @access public
         * @param string $sql SQL指令
         * @return false | integer
         */
        public function execute($sql)
        {
            $sql = $this->parseSql($sql);
            
            return $this->db->execute($sql);
        }
        
        /**
         * 启动事务
         * @access public
         * @return void
         */
        public function startTrans()
        {
            $this->commit();
            $this->db->startTrans();
            
            return;
        }
        
        /**
         * 提交事务
         * @access public
         * @return bool
         */
        public function commit()
        {
            return $this->db->commit();
        }
        
        /**
         * 事务回滚
         * @access public
         * @return bool
         */
        public function rollback()
        {
            return $this->db->rollback();
        }
        
        /**
         * 返回模型的错误信息
         * @access public
         * @return string
         */
        public function getError()
        {
            return $this->error;
        }
        
        /**
         * 返回数据库的错误信息
         * @access public
         * @return string
         */
        public function getDbError()
        {
            return $this->db->getError();
        }
        
        /**
         * getLastSql别名
         * @return string
         */
        public function _sql()
        {
            return $this->getLastSql();
        }
        
        /**
         * 返回最后执行的sql语句
         * @access public
         * @return string
         */
        public function getLastSql()
        {
            return $this->db->getLastSql($this->name);
        }
        
        /**
         * 设置数据对象值
         * @access public
         * @param mixed $data 数据
         * @return $this
         */
        public function data($data = '')
        {
            if ('' === $data && !empty($this->data)) {
                return $this->data;
            }
            if (is_object($data)) {
                $data = get_object_vars($data);
            } elseif (is_string($data)) {
                parse_str($data, $data);
            } elseif (!is_array($data)) {
                E(L('_DATA_TYPE_INVALID_'));
            }
            $this->data = $data;
            
            return $this;
        }
        
        /**
         * 指定当前的数据表
         * @access public
         * @param mixed $table
         * @return $this
         */
        public function table($table)
        {
            $prefix = $this->tablePrefix;
            if (is_array($table)) {
                $this->options['table'] = $table;
            } elseif (!empty($table)) {
                //将__TABLE_NAME__替换成带前缀的表名
                $table = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                    return $prefix . strtolower($match[1]);
                }, $table);
                $this->options['table'] = $table;
            }
            
            return $this;
        }
        
        /**
         * USING支持 用于多表删除
         * @access public
         * @param mixed $using
         * @return $this
         */
        public function using($using)
        {
            $prefix = $this->tablePrefix;
            if (is_array($using)) {
                $this->options['using'] = $using;
            } elseif (!empty($using)) {
                //将__TABLE_NAME__替换成带前缀的表名
                $using = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                    return $prefix . strtolower($match[1]);
                }, $using);
                $this->options['using'] = $using;
            }
            
            return $this;
        }
        
        /**
         * 查询SQL组装 join
         * @access public
         * @param mixed  $join
         * @param string $type JOIN类型
         * @return $this
         */
        public function join($join, $type = 'INNER')
        {
            $prefix = $this->tablePrefix;
            if (is_array($join)) {
                foreach ($join as &$_join) {
                    $_join = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                        return $prefix . strtolower($match[1]);
                    }, $_join);
                    $_join = false !== stripos($_join, 'JOIN') ? $_join : $type . ' JOIN ' . $_join;
                }
                $this->options['join'] = $join;
            } elseif (!empty($join)) {
                //将__TABLE_NAME__字符串替换成带前缀的表名
                $join = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                    return $prefix . strtolower($match[1]);
                }, $join);
                $this->options['join'][] = false !== stripos($join, 'JOIN') ? $join : $type . ' JOIN ' . $join;
            }
            
            return $this;
        }
        
        /**
         * 查询SQL组装 union
         * @access public
         * @param mixed   $union
         * @param boolean $all
         * @return $this
         */
        public function union($union, $all = false)
        {
            if (empty($union)) {
                return $this;
            }
            if ($all) {
                $this->options['union']['_all'] = true;
            }
            if (is_object($union)) {
                $union = get_object_vars($union);
            }
            // 转换union表达式
            if (is_string($union)) {
                $prefix = $this->tablePrefix;
                //将__TABLE_NAME__字符串替换成带前缀的表名
                $options = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                    return $prefix . strtolower($match[1]);
                }, $union);
            } elseif (is_array($union)) {
                if (isset($union[0])) {
                    $this->options['union'] = array_merge($this->options['union'], $union);
                    
                    return $this;
                } else {
                    $options = $union;
                }
            } else {
                E(L('_DATA_TYPE_INVALID_'));
            }
            $this->options['union'][] = $options;
            
            return $this;
        }
        
        /**
         * 查询缓存
         * @access public
         * @param mixed  $key
         * @param int    $expire
         * @param string $type
         * @return $this
         */
        public function cache($key = true, $expire = null, $type = '')
        {
            // 增加快捷调用方式 cache(10) 等同于 cache(true, 10)
            if (is_numeric($key) && is_null($expire)) {
                $expire = $key;
                $key = true;
            }
            if (false !== $key) {
                $this->options['cache'] = ['key' => $key, 'expire' => $expire, 'type' => $type];
            }
            
            return $this;
        }
        
        /**
         * 指定查询字段 支持字段排除
         * @access public
         * @param mixed   $field
         * @param boolean $except 是否排除
         * @return $this
         */
        public function field($field, $except = false)
        {
            // 获取全部字段
            if (true === $field) {
                $fields = $this->getDbFields();
                $field = $fields ?: '*';
                // 字段排除
            } elseif ($except) {
                if (is_string($field)) {
                    $field = explode(',', $field);
                }
                $fields = $this->getDbFields();
                $field = $fields ? array_diff($fields, $field) : $field;
            }
            $this->options['field'] = $field;
            
            return $this;
        }
        
        /**
         * 指定查询数量
         * @access public
         * @param mixed $offset 起始位置
         * @param mixed $length 查询数量
         * @return $this
         */
        public function limit($offset, $length = null)
        {
            if (is_null($length) && strpos($offset, ',')) {
                list($offset, $length) = explode(',', $offset);
            }
            $this->options['limit'] = intval($offset) . ($length ? ',' . intval($length) : '');
            
            return $this;
        }
        
        /**
         * 指定分页
         * @access public
         * @param mixed $page     页数
         * @param mixed $listRows 每页数量
         * @return $this
         */
        public function page($page, $listRows = null)
        {
            if (is_null($listRows) && strpos($page, ',')) {
                list($page, $listRows) = explode(',', $page);
            }
            $this->options['page'] = [intval($page), intval($listRows)];
            
            return $this;
        }
        
        /**
         * 查询注释
         * @access public
         * @param string $comment 注释
         * @return $this
         */
        public function comment($comment)
        {
            $this->options['comment'] = $comment;
            
            return $this;
        }
        
        /**
         * 参数绑定
         * @access public
         * @param string $key   参数名
         * @param mixed  $value 绑定的变量及绑定参数
         * @return $this
         */
        public function bind($key, $value = false)
        {
            if (is_array($key)) {
                $this->options['bind'] = $key;
            } else {
                $num = func_num_args();
                if ($num > 2) {
                    $params = func_get_args();
                    array_shift($params);
                    $this->options['bind'][$key] = $params;
                } else {
                    $this->options['bind'][$key] = $value;
                }
            }
            
            return $this;
        }
        
    }
