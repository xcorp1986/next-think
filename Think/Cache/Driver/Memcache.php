<?php


namespace Think\Cache\Driver;

use Think\BaseException;
use Think\Cache;


/**
 * Memcache缓存驱动
 */
class Memcache extends Cache
{

    /**
     * @param array $options 缓存参数
     *
     * @throws BaseException
     */
    public function __construct(array $options = [])
    {
        if (!extension_loaded('memcache')) {
            throw new BaseException(L('_NOT_SUPPORT_').':memcache');
        }

        $options = array_merge(
            [
                'host'       => C('MEMCACHE_HOST') ?: '127.0.0.1',
                'port'       => C('MEMCACHE_PORT') ?: 11211,
                'timeout'    => C('DATA_CACHE_TIMEOUT'),
                'persistent' => false,
            ],
            $options
        );

        $this->options = $options;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new \Memcache;
        $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
    }

    /**
     * 读取缓存
     *
     * @param string $name 缓存变量名
     *
     * @return mixed
     */
    public function get($name)
    {
//            N('cache_read', 1);

        return $this->handler->get($this->options['prefix'].$name);
    }

    /**
     * 写入缓存
     *
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param int $expire 有效时间（秒）
     *
     * @return bool
     */
    public function set($name, $value, $expire = null)
    {
//            N('cache_write', 1);
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $name = $this->options['prefix'].$name;
        if ($this->handler->set($name, $value, 0, $expire)) {
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
     *
     * @param string $name 缓存变量名
     * @param bool $ttl
     *
     * @return bool
     */
    public function rm($name, $ttl = false)
    {
        $name = $this->options['prefix'].$name;

        return $ttl === false ?
            $this->handler->delete($name) :
            $this->handler->delete($name, $ttl);
    }

    /**
     * 清除缓存
     * @return bool
     */
    public function clear()
    {
        return $this->handler->flush();
    }
}
