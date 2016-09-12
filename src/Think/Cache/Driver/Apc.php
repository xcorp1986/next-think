<?php


    namespace Think\Cache\Driver;

    use Think\Cache;


    /**
     * Apc缓存驱动
     */
    class Apc extends Cache
    {

        /**
         * @param array $options 缓存参数
         * @access public
         */
        public function __construct($options = [])
        {
            if (!function_exists('apc_cache_info')) {
                E(L('_NOT_SUPPORT_') . ':Apc');
            }
            $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
            $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
            $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        }

        /**
         * 读取缓存
         * @access public
         * @param string $name 缓存变量名
         * @return mixed
         */
        public function get($name)
        {
            N('cache_read', 1);

            return apc_fetch($this->options['prefix'] . $name);
        }

        /**
         * 写入缓存
         * @access public
         * @param string $name   缓存变量名
         * @param mixed  $value  存储数据
         * @param int    $expire 有效时间（秒）
         * @return bool
         */
        public function set($name, $value, $expire = null)
        {
            N('cache_write', 1);
            if (is_null($expire)) {
                $expire = $this->options['expire'];
            }
            $name = $this->options['prefix'] . $name;
            if ($result = apc_store($name, $value, $expire)) {
                if ($this->options['length'] > 0) {
                    // 记录缓存队列
                    $this->queue($name);
                }
            }

            return $result;
        }

        /**
         * 删除缓存
         * @access public
         * @param string $name 缓存变量名
         * @return bool
         */
        public function rm($name)
        {
            return apc_delete($this->options['prefix'] . $name);
        }

        /**
         * 清除缓存
         * @access public
         * @return bool
         */
        public function clear()
        {
            return apc_clear_cache();
        }

    }
