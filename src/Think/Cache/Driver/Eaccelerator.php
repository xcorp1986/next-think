<?php


    namespace Think\Cache\Driver;

    use Think\Cache;


    /**
     * Eaccelerator缓存驱动
     */
    class Eaccelerator extends Cache
    {

        /**
         * @param array $options 缓存参数
         * @access public
         */
        public function __construct(array $options = [])
        {
            $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
            $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
            $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
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

            return eaccelerator_get($this->options['prefix'] . $name);
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
            eaccelerator_lock($name);
            if (eaccelerator_put($name, $value, $expire)) {
                if ($this->options['length'] > 0) {
                    // 记录缓存队列
                    $this->queue($name);
                }

                return true;
            }

            return false;
        }


        /**
         * 删除缓存
         * @access public
         * @param string $name 缓存变量名
         * @return bool
         */
        public function rm($name)
        {
            return eaccelerator_rm($this->options['prefix'] . $name);
        }

    }