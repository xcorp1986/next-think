<?php


    /**
     * 普通模式定义
     */
    return [
        // 配置文件
        'config' => [
            THINK_PATH . 'Conf/convention.php',   // 系统惯例配置
            CONF_PATH . 'config' . CONF_EXT,      // 应用公共配置
        ],

        // 函数和类文件
        'core'   => [
            THINK_PATH . 'Common/function.php',
            COMMON_PATH . 'Common/function.php',

        ],

        // 行为扩展定义
        'tags'   => [
            'app_init'        => [
//                \Behavior\BuildLiteBehavior::class, // 生成运行Lite文件
            ],
            'app_begin'       => [
                \Behavior\ReadHtmlCacheBehavior::class, // 读取静态缓存
            ],
            'app_end'         => [
                \Behavior\ShowPageTraceBehavior::class, // 页面Trace显示
            ],
            'view_parse'      => [
                \Behavior\ParseTemplateBehavior::class, // 模板解析 支持PHP、内置模板引擎和第三方模板引擎
            ],
            'template_filter' => [
                \Behavior\ContentReplaceBehavior::class, // 模板输出替换
            ],
            'view_filter'     => [
                \Behavior\WriteHtmlCacheBehavior::class, // 写入静态缓存
            ],
        ],
    ];
