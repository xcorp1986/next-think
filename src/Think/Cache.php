<?php
    
    namespace Think;
    
    /**
     * 缓存管理类
     * Class Cache
     * @package Think
     * @method get($name)
     * @method set($name, $value)
     * @method rm($name)
     */
    class Cache
    {
        
        /**
         * 操作句柄
         * @var string
         * @access protected
         */
        protected $handler;
        
        /**
         * 缓存连接参数
         * @var int
         * @access protected
         */
        protected $options = [];
        
        /**
         * 连接缓存
         * @access public
         * @param string $type    缓存类型
         * @param array  $options 配置数组
         * @return object
         */
        public function connect($type = '', array $options = [])
        {
            if (empty($type)) {
                $type = C('DATA_CACHE_TYPE');
            }
            $class = strpos($type, '\\') ? $type : 'Think\\Cache\\Driver\\' . ucwords(strtolower($type));
            if (class_exists($class)) {
                $cache = new $class($options);
            } else {
                E(L('_CACHE_TYPE_INVALID_') . ':' . $type);
            }
            
            return $cache;
        }
        
        /**
         * 取得缓存类实例
         * @static
         * @access public
         * @return mixed
         */
        public static function getInstance($type = '', $options = [])
        {
            static $_instance = [];
            $guid = $type . to_guid_string($options);
            if (!isset($_instance[$guid])) {
                $obj = new Cache();
                $_instance[$guid] = $obj->connect($type, $options);
            }
            
            return $_instance[$guid];
        }
    
        /**
         * @param $name
         * @return mixed
         */
        public function __get($name)
        {
            return $this->get($name);
        }
    
        /**
         * @param $name
         * @param $value
         * @return mixed
         */
        public function __set($name, $value)
        {
            return $this->set($name, $value);
        }
    
        /**
         * @param $name
         */
        public function __unset($name)
        {
            $this->rm($name);
        }
    
        /**
         * @param $name
         * @param $value
         */
        public function setOptions($name, $value)
        {
            $this->options[$name] = $value;
        }
    
        /**
         * @param $name
         * @return mixed
         */
        public function getOptions($name)
        {
            return $this->options[$name];
        }
        
        /**
         * 队列缓存
         * @access protected
         * @param string $key 队列名
         * @return mixed
         */
        protected function queue($key)
        {
            static $_handler = [
                'file'   => ['F', 'F'],
                'xcache' => ['xcache_get', 'xcache_set'],
                'apc'    => ['apc_fetch', 'apc_store'],
            ];
            $queue = isset($this->options['queue']) ? $this->options['queue'] : 'file';
            $fun = isset($_handler[$queue]) ? $_handler[$queue] : $_handler['file'];
            $queue_name = isset($this->options['queue_name']) ? $this->options['queue_name'] : 'think_queue';
            $value = $fun[0]($queue_name);
            if (!$value) {
                $value = [];
            }
            // 进列
            if (false === array_search($key, $value)) {
                array_push($value, $key);
            }
            if (count($value) > $this->options['length']) {
                // 出列
                $key = array_shift($value);
                // 删除缓存
                $this->rm($key);
                if (APP_DEBUG) {
                    //调试模式下，记录出列次数
                    N($queue_name . '_out_times', 1);
                }
            }
            
            return $fun[1]($queue_name, $value);
        }
        
        /**
         * @param $method
         * @param $args
         * @return mixed|void
         */
        public function __call($method, $args)
        {
            //调用缓存类型自己的方法
            if (method_exists($this->handler, $method)) {
                return call_user_func_array([$this->handler, $method], $args);
            } else {
                E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
                
                return;
            }
        }
    }