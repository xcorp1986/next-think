<?php
    
    /**
     * 系统常量定义
     */
    defined('APP_PATH') || die('请在入口文件中定义常量APP_PATH');
    
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
//    const URL_COMMON = 0;  //普通模式
//    const URL_PATHINFO = 1;  //PATHINFO模式
    const URL_REWRITE = 2;  //REWRITE模式
    const URL_COMPAT = 3;  // 兼容模式
    
    // 应用状态 加载对应的配置文件
    defined('APP_STATUS') || define('APP_STATUS', null);
    // 是否调试模式
    defined('APP_DEBUG') || define('APP_DEBUG', false);
    // 应用公共目录
    defined('COMMON_PATH') || define('COMMON_PATH', APP_PATH . 'Common/');
    // 系统运行时目录
    defined('RUNTIME_PATH') || define('RUNTIME_PATH', APP_PATH . 'Runtime/');
    // 应用静态目录
    defined('HTML_PATH') || define('HTML_PATH', APP_PATH . 'Html/');
    // 应用配置目录
    defined('CONF_PATH') || define('CONF_PATH', COMMON_PATH . 'Conf/');
    // 应用语言目录
    defined('LANG_PATH') || define('LANG_PATH', COMMON_PATH . 'Lang/');
    // 应用日志目录
    defined('LOG_PATH') || define('LOG_PATH', RUNTIME_PATH . 'Logs/');
    // 应用缓存目录
    defined('TEMP_PATH') || define('TEMP_PATH', RUNTIME_PATH . 'Temp/');
    // 应用数据目录
    defined('DATA_PATH') || define('DATA_PATH', RUNTIME_PATH . 'Data/');
    // 应用模板缓存目录
    defined('CACHE_PATH') || define('CACHE_PATH', RUNTIME_PATH . 'Cache/');
    // 配置文件解析方法
    defined('CONF_PARSE') || define('CONF_PARSE', '');
    define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')));
    define('IS_WIN', strpos(PHP_OS, 'WIN'));
    define('IS_CLI', PHP_SAPI == 'cli');