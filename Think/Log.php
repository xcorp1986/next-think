<?php

namespace Think;

/**
 * 日志处理类
 * Class Log
 * @package Think
 */
class Log
{

    // 日志级别 从上到下，由低到高
    // 严重错误: 导致系统崩溃无法使用
    const EMERG = 'EMERG';
    // 警戒性错误: 必须被立即修改的错误
    const ALERT = 'ALERT';
    // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const CRIT = 'CRIT';
    // 一般错误: 一般性错误
    const ERR = 'ERR';
    // 警告性错误: 需要发出警告的错误
    const WARN = 'WARN';
    // 通知: 程序可以运行但是还不够完美的错误
    const NOTICE = 'NOTIC';
    // 信息: 程序输出信息
    const INFO = 'INFO';
    // 调试: 调试信息
    const DEBUG = 'DEBUG';
    // SQL：SQL语句 注意只在调试模式开启时有效
    const SQL = 'SQL';

    /**
     * @var array $log 日志信息
     */
    protected static $log = [];

    /**
     * @var null $storage 日志存储
     */
    protected static $storage = null;

    /**
     * 日志初始化
     *
     * @param array $config
     */
    public static function init(array $config = [])
    {
        $type = isset($config['type']) ? $config['type'] : 'File';
        $class = strpos($type, '\\') ? $type : 'Think\\Log\\Driver\\'.ucwords(strtolower($type));
        unset($config['type']);
        self::$storage = new $class($config);
    }

    /**
     * 记录日志 并且会过滤未经设置的级别
     * @static
     * @access public
     *
     * @param string $message 日志信息
     * @param string $level 日志级别
     * @param bool $record 是否强制记录
     *
     * @return void
     */
    public static function record($message, $level = self::ERR, $record = false)
    {
        if ($record || false !== strpos(C('LOG_LEVEL'), $level)) {
            self::$log[] = "{$level}: {$message}\r\n";
        }
    }

    /**
     * 日志保存
     * @static
     * @access public
     *
     * @param string $type
     * @param string $destination 写入目标
     *
     * @return void
     */
    public static function save($type = '', $destination = '')
    {
        if (empty(self::$log)) {
            return;
        }

        if (empty($destination)) {
            $destination = C('LOG_PATH').date('y_m_d').'.log';
        }
        if (!self::$storage) {
            $type = $type ?: C('LOG_TYPE');
            $class = 'Think\\Log\\Driver\\'.ucwords($type);
            self::$storage = new $class();
        }
        $message = implode('', self::$log);
        self::$storage->write($message, $destination);
        // 保存后清空日志缓存
        self::$log = [];
    }

    /**
     * 日志直接写入
     * @static
     * @access public
     *
     * @param string $message 日志信息
     * @param string $level 日志级别
     * @param string $type 日志记录方式
     * @param string $destination 写入目标
     *
     * @return void
     */
    public static function write($message, $level = self::ERR, $type = '', $destination = '')
    {
        if (!self::$storage) {
            $type = $type ?: C('LOG_TYPE');
            $class = 'Think\\Log\\Driver\\'.ucwords($type);
            $config['log_path'] = C('LOG_PATH');
            self::$storage = new $class($config);
        }
        if (empty($destination)) {
            $destination = C('LOG_PATH').date('y_m_d').'.log';
        }
        self::$storage->write("{$level}: {$message}", $destination);
    }
}