<?php

namespace Think;

/**
 * 引导类
 */
final class Think
{

    /**
     * @var string 版本号
     */
    const VERSION = '3.2.3';

    /**
     * 应用程序初始化
     * @access public
     * @return void
     */
    public static function init()
    {
        /*
         * 设定错误和异常处理
         */
        register_shutdown_function([__CLASS__, 'fatalError']);
        set_error_handler([__CLASS__, 'appError']);

        /*
         * 初始化文件存储方式
         */
        Storage::connect();

        /**
         * 检查核心必须文件
         */
        if (!Storage::has(__DIR__.'/../Conf/core.php') ||
            !Storage::has(__DIR__.'/../Conf/config.php') ||
            !Storage::has(__DIR__.'/../Conf/tags.php')
        ) {
            static::halt('系统核心文件缺失');
        }
        /*
         * 加载核心文件
         */
        $mode = include __DIR__.'/../Conf/core.php';
        foreach ($mode as $file) {
            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                include $file;
            }
        }

        /*
         * 加载应用模式配置文件
         */
        $config = include __DIR__.'/../Conf/config.php';
        foreach ($config as $key => $file) {
            is_numeric($key) ? C(load_config($file)) : C($key, load_config($file));
        }

        /*
         * 加载模式行为定义
         */
        $tags = include __DIR__.'/../Conf/tags.php';
        if (isset($tags)) {
            is_array($tags) && Hook::import($tags);
        }

        /*
         * 加载应用行为
         */
        if (is_file(CONF_PATH.'tags.php')) {
            $appBehaviors = include CONF_PATH.'tags.php';
            is_array($appBehaviors) && Hook::import($appBehaviors);
        }

        /*
         * 加载框架底层语言包
         */
        L(include __DIR__.'/../Lang/'.C('DEFAULT_LANG').'.php');

        if (APP_DEBUG) {
            // 调试模式加载系统默认的配置文件
            C(include __DIR__.'/../Conf/debug.php');
            // 读取应用调试配置文件
            if (is_file(CONF_PATH.'debug.php')) {
                C(include CONF_PATH.'debug.php');
            }
        }

        /*
         * 设置系统时区
         * @todo move to other place
         */
        date_default_timezone_set(C('DEFAULT_TIMEZONE'));

        /*
         * 记录加载文件时间
         */
        G('loadTime');

    }

    /**
     * 自定义错误处理
     * @access public
     *
     * @param int $errNo 错误类型
     * @param string $errStr 错误信息
     * @param string $errFile 错误文件
     * @param int $errLine 错误行数
     *
     * @return void
     */
    public static function appError($errNo, $errStr, $errFile, $errLine)
    {
        switch ($errNo) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                ob_end_clean();
                $errorStr = "$errStr ".$errFile." 第 $errLine 行.";
                static::halt($errorStr);
                break;
            default:
                $errorStr = "[$errNo] $errStr ".$errFile." 第 $errLine 行.";
                static::trace($errorStr, '', 'NOTIC');
                break;
        }
    }

    /**
     * 致命错误捕获
     */
    public static function fatalError()
    {
        if ($e = error_get_last()) {
            switch ($e['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    static::halt($e);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 错误输出
     *
     * @param mixed $error 错误
     *
     * @return void
     */
    public static function halt($error)
    {
        $e = [];
        if (APP_DEBUG || IS_CLI) {
            //调试模式下输出错误信息
            if (!is_array($error)) {
                $trace = debug_backtrace();
                $e['message'] = $error;
                $e['file'] = $trace[0]['file'];
                $e['line'] = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace'] = ob_get_clean();
            } else {
                $e = $error;
            }
            if (IS_CLI) {
                echo iconv(
                        'UTF-8',
                        'gbk',
                        $e['message']
                    ).PHP_EOL.'FILE: '.$e['file'].'('.$e['line'].')'.PHP_EOL.$e['trace'];

                return;
            }
        } else {
            //否则定向到错误页面
            $error_page = C('ERROR_PAGE');
            if (!empty($error_page)) {
                redirect($error_page);
            } else {
                $message = is_array($error) ? $error['message'] : $error;
                $e['message'] = C('SHOW_ERROR_MSG') ? $message : C('ERROR_MESSAGE');
            }
        }
        // 包含异常页面模板
        $exceptionFile = C('TMPL_EXCEPTION_FILE', null, C('TMPL_EXCEPTION_FILE'));
        /** @noinspection PhpIncludeInspection */
        include $exceptionFile;
        return;
    }

    /**
     * 添加和获取页面Trace记录
     *
     * @param string $value 变量
     * @param string $label 标签
     * @param string $level 日志级别(或者页面Trace的选项卡)
     * @param bool $record 是否记录日志
     *
     * @return array
     */
    public static function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
    {
        static $_trace = [];
        // 获取trace信息
        if ('[think]' === $value) {
            return $_trace;
        } else {
            $info = ($label ? $label.':' : '').print_r($value, true);
            $level = strtoupper($level);

            if ((defined('IS_AJAX') && IS_AJAX) || !C('SHOW_PAGE_TRACE') || $record) {
                Log::record($info, $level, $record);
            } else {
                if (!isset($_trace[$level]) || count($_trace[$level]) > C('TRACE_MAX_RECORD')) {
                    $_trace[$level] = [];
                }
                $_trace[$level][] = $info;
            }
        }
    }

    /**
     * 禁止clone对象
     */
    public function __clone()
    {
        trigger_error('Cloning '.__CLASS__.' is not allowed.', E_USER_ERROR);
    }

    /**
     * 禁止反序列化对象
     */
    public function __wakeup()
    {
        trigger_error('Unserializing '.__CLASS__.' is not allowed.', E_USER_ERROR);
    }
}
