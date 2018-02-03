<?php

namespace Think;

/**
 * 文件存储类
 * Class Storage
 * @deprecated
 * @package Think
 * @method static load($_filename, $vars = null) 加载文件
 * @method static put($filename, $content) 文件写入
 * @method static get($filename, $name) 读取文件信息
 * @method static has($filename) 文件是否存在
 * @method static unlink($filename) 文件删除
 * @method static append($filename, $content) 文件追加写入
 * @method static read($filename) 读取文件内容
 */
class Storage
{

    /**
     * 操作句柄
     * @var string
     */
    protected static $handler;

    /**
     * 连接操作句柄
     * @access   public
     *
     * @param string $type 文件类型
     *
     * @return void
     */
    public static function connect($type = 'File')
    {
        $class = __NAMESPACE__.'\\Storage\\Driver\\'.ucwords($type);
        static::$handler = new $class;
    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        //静态调用驱动的方法
        if (method_exists(static::$handler, $method)) {
            return call_user_func_array([static::$handler, $method], $args);
        }
    }
}
