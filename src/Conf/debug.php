<?php
    
    /**
     * 默认的调试模式配置文件
     * 调试模式下面默认设置 可以在应用配置目录下重新定义 debug.php 覆盖
     */
    return [
        // 进行日志记录
        'LOG_RECORD'           => true,
        // 是否记录异常信息日志
        'LOG_EXCEPTION_RECORD' => true,
        // 允许记录的日志级别
        'LOG_LEVEL'            => 'EMERG,ALERT,CRIT,ERR,WARN,NOTIC,INFO,DEBUG,SQL',
        // 字段缓存信息
        'DB_FIELDS_CACHE'      => false,
        // 开启调试模式 记录SQL日志
        'DB_DEBUG'             => true,
        // 是否开启模板编译缓存,设为false则每次都会重新编译
        'TMPL_CACHE_ON'        => false,
        // 是否去除模板文件里面的html空格与换行
        'TMPL_STRIP_SPACE'     => false,
        // 显示错误信息
        'SHOW_ERROR_MSG'       => true,
        // URL区分大小写
        'URL_CASE_INSENSITIVE' => false,
        //页面trace
        'SHOW_PAGE_TRACE'      => false,
    ];