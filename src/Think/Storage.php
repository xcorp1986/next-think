<?php

    namespace Think;

    /**
     * 文件存储类
     * Class Storage
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
         * @access protected
         */
        static protected $handler;

        /**
         * 连接操作句柄
         * @access public
         * @param string $type    文件类型
         * @param array  $options 配置数组
         * @return void
         */
        static public function connect($type = 'File')
        {
            $class = 'Think\\Storage\\Driver\\' . ucwords($type);
            self::$handler = \Think\Think::instance($class);
        }

        static public function __callStatic($method, $args)
        {
            //静态调用驱动的方法
            if (method_exists(self::$handler, $method)) {
                return call_user_func_array([self::$handler, $method], $args);
            }
        }
    }
