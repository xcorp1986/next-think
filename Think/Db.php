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
     *
     * @param mixed $config 连接配置
     * @return $this
     * @throws BaseException
     */
    public static function getInstance(array $config = [])
    {
        !$config && $config = static::getConfig();
        $md5 = to_guid_string($config);
        if (!isset(static::$instance[$md5])) {
            // 兼容mysqli
            if ('mysqli' == $config['type']) {
                $config['type'] = 'mysql';
            }
            $class = __NAMESPACE__.'\\Db\\Driver\\'.ucwords(strtolower($config['type']));
            try {
                if (!class_exists($class)) {
                    throw new DbDriverNotFoundException();
                }
                static::$instance[$md5] = new $class($config);
            } catch (DbDriverNotFoundException $e) {
                throw new BaseException($e->getMessage().'类名:'.$class);
            }
        }
        static::$_instance = static::$instance[$md5];

        return static::$_instance;
    }

    /**
     * 数据库连接参数解析
     * @static

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
        return call_user_func_array([static::$_instance, $method], $params);
    }

}
