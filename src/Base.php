<?php

    /**
     * 系统常量定义
     */
    defined('APP_PATH') or die('请在入口文件中定义常量APP_PATH');

    /*
     * 记录开始运行时间
     */
    $GLOBALS['_beginTime'] = microtime(true);

    /*
     * 记录内存初始使用
     */
    define('MEMORY_LIMIT_ON', function_exists('memory_get_usage'));
    if (MEMORY_LIMIT_ON) $GLOBALS['_startUseMems'] = memory_get_usage();

    /*
     * 版本信息
     */
    const THINK_VERSION = '3.2.3';

    /*
     * URL 模式定义
     */
    const URL_COMMON = 0;  //普通模式
    const URL_PATHINFO = 1;  //PATHINFO模式
    const URL_REWRITE = 2;  //REWRITE模式
    const URL_COMPAT = 3;  // 兼容模式

    /*
     * 类文件后缀(统一都是这个后缀了) modified by Kwan 2016-9-12
     */
    const EXT = '.php';

    defined('THINK_PATH') or define('THINK_PATH', __DIR__ . '/');
    defined('APP_STATUS') or define('APP_STATUS', null); // 应用状态 加载对应的配置文件
    defined('APP_DEBUG') or define('APP_DEBUG', false); // 是否调试模式
    defined('APP_MODE') or define('APP_MODE', 'common'); // 应用模式 默认为普通模式
    //@todo have effect on BuildLiteBehavior
//    defined('CORE_PATH') or define('CORE_PATH', LIB_PATH . 'Think/'); // Think类库目录
//    defined('BEHAVIOR_PATH') or define('BEHAVIOR_PATH', __DIR__ . '/Behavior/'); // 行为类库目录
//    defined('MODE_PATH') or define('MODE_PATH', __DIR__ . '/Mode/'); // 系统应用模式目录
    defined('COMMON_PATH') or define('COMMON_PATH', APP_PATH . 'Common/'); // 应用公共目录
    defined('RUNTIME_PATH') or define('RUNTIME_PATH', APP_PATH . 'Runtime/');   // 系统运行时目录
    defined('ADDON_PATH') or define('ADDON_PATH', APP_PATH . 'Addon');
    defined('HTML_PATH') or define('HTML_PATH', APP_PATH . 'Html/'); // 应用静态目录
    defined('CONF_PATH') or define('CONF_PATH', COMMON_PATH . 'Conf/'); // 应用配置目录
    defined('LANG_PATH') or define('LANG_PATH', COMMON_PATH . 'Lang/'); // 应用语言目录
    defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'Logs/'); // 应用日志目录
    defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'Temp/'); // 应用缓存目录
    defined('DATA_PATH') or define('DATA_PATH', RUNTIME_PATH . 'Data/'); // 应用数据目录
    defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'Cache/'); // 应用模板缓存目录
    defined('CONF_EXT') or define('CONF_EXT', EXT); // 配置文件后缀
    defined('CONF_PARSE') or define('CONF_PARSE', '');    // 配置文件解析方法
    define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? 1 : 0);
    define('IS_WIN', strpos(PHP_OS, 'WIN') ? 1 : 0);
    define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);
    //    defined('LIB_PATH') or define('LIB_PATH', __DIR__ . '/'); // 系统核心类库目录@todo remove in future