<?php
    
    namespace Think;
    
    use Think\Exception\DbDriverNotFoundException;

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
         *
         * @param mixed $config 连接配置
         *
         * @throws DbDriverNotFoundException
         * @return $this 返回数据库驱动类
         */
        public static function getInstance(array $config = [])
        {
            $md5 = to_guid_string($config);
            if ( ! isset(self::$instance[$md5])) {
                // 获取数据库配置参数
                $options = self::getConfig();
                // 兼容mysqli
                if ('mysqli' == $options['type']) {
                    $options['type'] = 'mysql';
                }
                $class = 'Think\\Db\\Driver\\'.ucwords(strtolower($options['type']));
                try {
                    if ( ! class_exists($class)) {
                        throw new DbDriverNotFoundException();
                    }
                    self::$instance[$md5] = new $class($options);
                } catch (DbDriverNotFoundException $e) {
                    E($e->getMessage().'类名:'.$class);
                }
            }
            self::$_instance = self::$instance[$md5];
            
            return self::$_instance;
        }
        
        /**
         * 数据库连接参数解析
         * @static
         * @access private
         * @return array
         */
        private static function getConfig()
        {
            return [
                'type'        => C('DB_TYPE'),
                'username'    => C('DB_USER'),
                'password'    => C('DB_PWD'),
                'hostname'    => C('DB_HOST'),
                'hostport'    => C('DB_PORT'),
                'database'    => C('DB_NAME'),
                'dsn'         => C('DB_DSN'),
                'params'      => C('DB_PARAMS'),
                'charset'     => C('DB_CHARSET'),
                'deploy'      => C('DB_DEPLOY_TYPE'),
                'rw_separate' => C('DB_RW_SEPARATE'),
                'master_num'  => C('DB_MASTER_NUM'),
                'slave_no'    => C('DB_SLAVE_NO'),
                'debug'       => C('DB_DEBUG', null, APP_DEBUG),
            ];
        }
        
        /**
         * 静态调用驱动类的方法
         *
         * @param $method
         * @param $params
         *
         * @return mixed
         */
        public static function __callStatic($method, $params)
        {
            return call_user_func_array([self::$_instance, $method], $params);
        }
        
    }
