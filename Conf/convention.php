<?php

/**
 * 惯例配置文件
 * 该文件请不要修改，如果要覆盖惯例配置的值，可在应用配置文件中设定和惯例不符的配置项
 * 配置名称大小写任意，系统会统一转换成小写
 * 所有配置参数都可以在生效前动态改变
 */
use Think\Template\TagLib\Cx;
use Think\Url\UrlCaseSensitivity;
use Think\Url\UrlSchema;

return [
    /* 应用设定 */
    // 是否开启子域名部署
    'APP_SUB_DOMAIN_DEPLOY' => false,
    // 子域名部署规则
    'APP_SUB_DOMAIN_RULES'  => [],
    // 域名后缀 如果是com.cn net.cn 之类的后缀必须设置
    'APP_DOMAIN_SUFFIX'     => '',
    // 是否允许多模块 如果为false 则必须设置 DEFAULT_MODULE
    'MULTI_MODULE'          => true,
    'MODULE_DENY_LIST'      => ['Common', 'Runtime'],
    'CONTROLLER_LEVEL'      => 1,

    /* Cookie设置 */
    'COOKIE_EXPIRE'         => 0,
    // Cookie有效期
    'COOKIE_DOMAIN'         => '',
    // Cookie有效域名
    'COOKIE_PATH'           => '/',
    // Cookie路径
    'COOKIE_PREFIX'         => '',
    // Cookie前缀 避免冲突
    'COOKIE_SECURE'         => false,
    // Cookie安全传输
    'COOKIE_HTTPONLY'       => '',
    // Cookie httponly设置

    /* 默认设定 */
    'DEFAULT_M_LAYER'       => 'Model',
    // 默认的模型层名称
    'DEFAULT_C_LAYER'       => 'Controller',
    // 默认的控制器层名称
    'DEFAULT_V_LAYER'       => 'View',
    // 默认的视图层名称
    'DEFAULT_LANG'          => 'zh-cn',
    // 默认语言
    'DEFAULT_THEME'         => '',
    // 默认模板主题名称
    'DEFAULT_MODULE'        => 'Home',
    // 默认模块
    'DEFAULT_CONTROLLER'    => 'Index',
    // 默认控制器名称
    'DEFAULT_ACTION'        => 'index',
    // 默认操作名称
    'DEFAULT_CHARSET'       => 'utf-8',
    // 默认输出编码
    'DEFAULT_TIMEZONE'      => 'PRC',
    // 默认时区
    'DEFAULT_AJAX_RETURN'   => 'JSON',
    // 默认AJAX 数据返回格式,可选JSON XML ...
    'DEFAULT_JSONP_HANDLER' => 'jsonpReturn',
    // 默认JSONP格式返回的处理方法
    'DEFAULT_FILTER'        => 'htmlspecialchars',
    // 默认参数过滤方法 用于I函数...

    /* 数据库设置 */
    'DB_TYPE'               => '',
    // 数据库类型
    'DB_HOST'               => '',
    // 服务器地址
    'DB_NAME'               => '',
    // 数据库名
    'DB_USER'               => '',
    // 用户名
    'DB_PWD'                => '',
    // 密码
    'DB_PORT'               => '',
    // 端口
    'DB_PREFIX'             => '',
    // 数据库表前缀
    'DB_PARAMS'             => [],
    // 数据库连接参数
    'DB_DEBUG'              => true,
    // 数据库调试模式 开启后可以记录SQL日志
    'DB_FIELDS_CACHE'       => true,
    // 启用字段缓存
    'DB_CHARSET'            => 'utf8',
    // 数据库编码默认采用utf8
    'DB_DEPLOY_TYPE'        => 0,
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'DB_RW_SEPARATE'        => false,
    // 数据库读写是否分离 主从式有效
    'DB_MASTER_NUM'         => 1,
    // 读写分离后 主服务器数量
    'DB_SLAVE_NO'           => '',
    // 指定从服务器序号

    /* 数据缓存设置 */
    'DATA_CACHE_TIME'       => 0,
    // 数据缓存有效期 0表示永久缓存
    'DATA_CACHE_COMPRESS'   => false,
    // 数据缓存是否压缩缓存
    'DATA_CACHE_CHECK'      => false,
    // 数据缓存是否校验缓存
    'DATA_CACHE_PREFIX'     => '',
    // 缓存前缀
    'DATA_CACHE_TYPE'       => 'File',
    // 数据缓存类型,支持:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
    'DATA_CACHE_PATH'       => TEMP_PATH,
    // 缓存路径设置 (仅对File方式缓存有效)
    'DATA_CACHE_KEY'        => '',
    // 缓存文件KEY (仅对File方式缓存有效)
    'DATA_CACHE_SUBDIR'     => false,
    // 使用子目录缓存 (自动根据缓存标识的哈希创建子目录)
    'DATA_PATH_LEVEL'       => 1,
    // 子目录缓存级别

    /* 错误设置 */
    'ERROR_MESSAGE'         => '页面错误！请稍后再试～',
    //错误显示信息,非调试模式有效
    'ERROR_PAGE'            => '',
    // 错误定向页面
    'SHOW_ERROR_MSG'        => false,
    // 显示错误信息
    'TRACE_MAX_RECORD'      => 100,
    // 每个级别的错误信息 最大记录数

    /* 日志设置 */
    'LOG_RECORD'            => false,
    // 默认不记录日志
    'LOG_TYPE'              => 'File',
    // 日志记录类型 默认为文件方式
    'LOG_LEVEL'             => 'EMERG,ALERT,CRIT,ERR',
    // 允许记录的日志级别
    'LOG_FILE_SIZE'         => 2097152,
    // 日志文件大小限制
    'LOG_EXCEPTION_RECORD'  => false,
    // 是否记录异常信息日志

    /* SESSION设置 */
    // 是否自动开启Session
    'SESSION_AUTO_START'    => true,
    // session 配置数组 支持type name id path expire domain 等参数
    'SESSION_OPTIONS'       => [],
    // session hander类型 默认无需设置 除非扩展了session hander驱动
    'SESSION_TYPE'          => '',
    // session 前缀
    'SESSION_PREFIX'        => '',
    //sessionID的提交变量
    //'VAR_SESSION_ID'      =>  'session_id',

    /* 模板引擎设置 */
    // 默认模板输出类型
    'TMPL_CONTENT_TYPE'     => 'text/html',
    // 默认错误跳转对应的模板文件
    'TMPL_ACTION_ERROR'     => __DIR__.'/../Resources/dispatch_jump.tpl',
    // 默认成功跳转对应的模板文件
    'TMPL_ACTION_SUCCESS'   => __DIR__.'/../Resources/dispatch_jump.tpl',
    // 异常页面的模板文件
    'TMPL_EXCEPTION_FILE'   => __DIR__.'/../Resources/think_exception.tpl',
    // 自动侦测模板主题
    'TMPL_DETECT_THEME'     => false,
    // 默认模板文件后缀
    'TMPL_TEMPLATE_SUFFIX'  => '.html',
    //模板文件CONTROLLER_NAME与ACTION_NAME之间的分割符
    'TMPL_FILE_DEPR'        => '/',
    // 布局设置
    // 默认模板引擎 以下设置仅对使用Think模板引擎有效
    'TMPL_ENGINE_TYPE'      => 'Think',
    // 默认模板缓存后缀
    'TMPL_CACHFILE_SUFFIX'  => '.php',
    // 模板引擎禁用函数
    'TMPL_DENY_FUNC_LIST'   => 'echo,exit',
    // 默认模板引擎是否禁用PHP原生代码
    'TMPL_DENY_PHP'         => false,
    // 模板引擎普通标签开始标记
    'TMPL_L_DELIM'          => '{',
    // 模板引擎普通标签结束标记
    'TMPL_R_DELIM'          => '}',
    //@todo 统一标识为数组，模板统一一下语法，记住多种语法累不？
    // 模板变量识别。留空自动判断,参数为'obj'则表示对象
    'TMPL_VAR_IDENTIFY'     => 'array',
    // 是否去除模板文件里面的html空格与换行
    'TMPL_STRIP_SPACE'      => true,
    // 是否开启模板编译缓存,设为false则每次都会重新编译
    'TMPL_CACHE_ON'         => true,
    // 模板缓存前缀标识，可以动态改变
    'TMPL_CACHE_PREFIX'     => '',
    // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
    'TMPL_CACHE_TIME'       => 0,
    // 布局模板的内容替换标识
    'TMPL_LAYOUT_ITEM'      => '{__CONTENT__}',
    // 是否启用布局
    'LAYOUT_ON'             => false,
    // 当前布局名称 默认为layout
    'LAYOUT_NAME'           => 'layout',
    //特殊字符串替换
    'TMPL_PARSE_STRING'     => [],

    // Think模板引擎标签库相关设定
    // 标签库标签开始标记
    'TAGLIB_BEGIN'          => '<',
    // 标签库标签结束标记
    'TAGLIB_END'            => '>',
    // 内置标签库名称(标签使用不必指定标签库名称)
    'TAGLIB_BUILD_IN'       => [
        Cx::class,
    ],

    /* URL设置 */
    // 默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_CASE_INSENSITIVE'  => UrlCaseSensitivity::SENSITIVITY,
    // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式
    'URL_MODEL'             => UrlSchema::PATHINFO,
    // PATHINFO模式下，各参数之间的分割符号
    'URL_PATHINFO_DEPR'     => '/',
    // 用于兼容判断PATH_INFO 参数的SERVER替代变量列表
    'URL_PATHINFO_FETCH'    => 'ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL',
    // 获取当前页面地址的系统变量 默认为REQUEST_URI
    'URL_REQUEST_URI'       => 'REQUEST_URI',
    // URL伪静态后缀设置
    'URL_HTML_SUFFIX'       => 'html',
    // URL禁止访问的后缀设置
    'URL_DENY_SUFFIX'       => 'ico|png|gif|jpg',
    // URL变量绑定到Action方法参数
    'URL_PARAMS_BIND'       => true,
    // URL变量绑定的类型 0 按变量名绑定 1 按变量顺序绑定
    'URL_PARAMS_BIND_TYPE'  => 0,
    // 是否开启URL路由
    'URL_ROUTER_ON'         => false,
    // 默认路由规则 针对模块
    'URL_ROUTE_RULES'       => [],
    // URL映射定义规则
    'URL_MAP_RULES'         => [],

    /* 系统变量名称设置 */
    // 默认模块获取变量
    'VAR_MODULE'            => 'm',
    // 默认控制器获取变量
    'VAR_CONTROLLER'        => 'c',
    // 默认操作获取变量
    'VAR_ACTION'            => 'a',
    // 默认的AJAX提交变量
    'VAR_AJAX_SUBMIT'       => 'ajax',
    // 兼容模式PATHINFO获取变量例如 ?s=/module/action/id/1 后面的参数取决于URL_PATHINFO_DEPR
    'VAR_PATHINFO'          => 's',
    'VAR_JSONP_HANDLER'     => 'callback',
    'VAR_TEMPLATE'          => 't',
    // 默认模板切换变量
    // 输入变量是否自动强制转换为字符串 如果开启则数组变量需要手动传入变量修饰符获取变量
    'VAR_AUTO_STRING'       => false,

    // 网页缓存控制
    'HTTP_CACHE_CONTROL'    => 'private',
    // 是否检查应用目录是否创建
    'CHECK_APP_DIR'         => true,
    // 文件上传方式
    'FILE_UPLOAD_TYPE'      => 'Local',

];
