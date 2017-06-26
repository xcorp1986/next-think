<?php

/**
 * 框架入口文件
 */
use Think\Think;

/**
 * 系统常量定义
 */
if (!defined('APP_PATH')) {
    @trigger_error('Please define APP_PATH', E_USER_ERROR);
}

/*
 * 记录开始运行时间
 */
$GLOBALS['_beginTime'] = microtime(true);

/*
 * 记录内存初始使用
 */
define('MEMORY_LIMIT_ON', function_exists('memory_get_usage'));
if (MEMORY_LIMIT_ON) {
    $GLOBALS['_startUseMems'] = memory_get_usage();
}

/*
 * URL 模式定义
 */
//const URL_REWRITE = 2;  //REWRITE模式
//const URL_COMPAT = 3;  // 兼容模式

// 是否调试模式
defined('APP_DEBUG') || define('APP_DEBUG', false);
// 应用公共目录
/** @noinspection PhpUndefinedConstantInspection */
defined('COMMON_PATH') || define('COMMON_PATH', APP_PATH.'Common/');
// 系统运行时目录
/** @noinspection PhpUndefinedConstantInspection */
defined('RUNTIME_PATH') || define('RUNTIME_PATH', APP_PATH.'Runtime/');
// 应用静态目录
/** @noinspection PhpUndefinedConstantInspection */
defined('HTML_PATH') || define('HTML_PATH', APP_PATH.'Html/');
// 应用配置目录
defined('CONF_PATH') || define('CONF_PATH', COMMON_PATH.'Conf/');
// 应用语言目录
defined('LANG_PATH') || define('LANG_PATH', COMMON_PATH.'Lang/');
// 应用日志目录
defined('LOG_PATH') || define('LOG_PATH', RUNTIME_PATH.'Logs/');
// 应用缓存目录
defined('TEMP_PATH') || define('TEMP_PATH', RUNTIME_PATH.'Temp/');
// 应用数据目录
defined('DATA_PATH') || define('DATA_PATH', RUNTIME_PATH.'Data/');
// 应用模板缓存目录
defined('CACHE_PATH') || define('CACHE_PATH', RUNTIME_PATH.'Cache/');
// 配置文件解析方法
defined('CONF_PARSE') || define('CONF_PARSE', '');
define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')));
define('IS_WIN', strpos(PHP_OS, 'WIN'));
define('IS_CLI', PHP_SAPI == 'cli');

if (!IS_CLI) {
    // 当前文件名
    if (!defined('_PHP_FILE_')) {
        if (IS_CGI) {
            //CGI/FASTCGI模式下
            $_temp = explode('.php', $_SERVER['PHP_SELF']);
            define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0].'.php'), '/'));
        } else {
            define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
        }
    }
}
/*
 * 应用程序初始化
 */
Think::init();