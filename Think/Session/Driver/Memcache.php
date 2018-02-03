<?php

namespace Think\Session\Driver;

use SessionHandlerInterface;

final class Memcache implements SessionHandlerInterface
{
    protected $lifeTime = 3600;
    protected $sessionName = '';
    /**
     * @var \Memcache $handle
     */
    protected $handle = null;

    /**
     * 打开Session

     *
     * @param string $savePath
     * @param mixed $sessName
     *
     * @return bool
     */
    public function open($savePath, $sessName)
    {
        $this->lifeTime = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : $this->lifeTime;
        $options = [
            'timeout'    => C('SESSION_TIMEOUT') ? C('SESSION_TIMEOUT') : 1,
            'persistent' => C('SESSION_PERSISTENT') ? C('SESSION_PERSISTENT') : 0,
        ];
        $this->handle = new \Memcache;
        $hosts = explode(',', C('MEMCACHE_HOST'));
        $ports = explode(',', C('MEMCACHE_PORT'));
        foreach ($hosts as $i => $host) {
            $port = isset($ports[$i]) ? $ports[$i] : $ports[0];
            $this->handle->addServer($host, $port, true, 1, $options['timeout']);
        }

        return true;
    }

    /**
     * 关闭Session

     */
    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->handle->close();
        $this->handle = null;

        return true;
    }

    /**
     * 读取Session

     *
     * @param string $sessID
     *
     * @return array|string
     */
    public function read($sessID)
    {
        return $this->handle->get($this->sessionName.$sessID);
    }

    /**
     * 写入Session

     *
     * @param string $sessID
     * @param String $sessData
     *
     * @return bool
     */
    public function write($sessID, $sessData)
    {
        return $this->handle->set($this->sessionName.$sessID, $sessData, 0, $this->lifeTime);
    }

    /**
     * 删除Session

     *
     * @param string $sessID
     *
     * @return bool
     */
    public function destroy($sessID)
    {
        return $this->handle->delete($this->sessionName.$sessID);
    }

    /**
     * Session 垃圾回收

     *
     * @param string $sessMaxLifeTime
     *
     * @return bool
     */
    public function gc($sessMaxLifeTime)
    {
        return true;
    }
}
