<?php
    
    namespace Think;
    
    /**
     * 数据库中间层实现类
     * Class Db
     * @package Think
     */
    class Db
    {
        /**
         * 数据库连接实例
         * @var array $instance
         */
        private static $instance = [];
        
        /**
         * 当前数据库连接实例
         * @var null $_instance
         */
        private static $_instance = null;
        
        /**
         * 取得数据库类实例
         * @static
         * @access public
         * @param mixed $config 连接配置
         * @return \Think\Db\Driver 返回数据库驱动类
         */
        public static function getInstance(array $config = [])
        {
            $md5 = md5(serialize($config));
            if (!isset(self::$instance[$md5])) {
                // 解析连接参数 支持数组和字符串
                $options = self::parseConfig($config);
                // 兼容mysqli
                if ('mysqli' == $options['type']) {
                    $options['type'] = 'mysql';
                }
                $class = 'Think\\Db\\Driver\\' . ucwords(strtolower($options['type']));
                if (class_exists($class)) {
                    self::$instance[$md5] = new $class($options);
                } else {
                    // 类没有定义
                    E(L('_NO_DB_DRIVER_') . ': ' . $class);
                }
            }
            self::$_instance = self::$instance[$md5];
            
            return self::$_instance;
        }
        
        /**
         * 数据库连接参数解析
         * @static
         * @access private
         * @param mixed $config
         * @return array
         */
        private static function parseConfig(array $config = [])
        {
            $config = array_change_key_case($config);
            $config = [
                'type'        => $config['db_type'],
                'username'    => $config['db_user'],
                'password'    => $config['db_pwd'],
                'hostname'    => $config['db_host'],
                'hostport'    => $config['db_port'],
                'database'    => $config['db_name'],
                'dsn'         => $config['db_dsn'] ?: null,
                'params'      => $config['db_params'] ?: null,
                'charset'     => $config['db_charset'] ?: 'utf8',
                'deploy'      => $config['db_deploy_type'] ?: 0,
                'rw_separate' => $config['db_rw_separate'] ?: false,
                'master_num'  => $config['db_master_num'] ?: 1,
                'slave_no'    => $config['db_slave_no'] ?: '',
                'debug'       => $config['db_debug'] ?: APP_DEBUG,
            ];
            
            return $config;
        }
        
        /**
         * 静态调用驱动类的方法
         * @param $method
         * @param $params
         * @return mixed
         */
        public static function __callStatic($method, $params)
        {
            return call_user_func_array([self::$_instance, $method], $params);
        }
        
    }
